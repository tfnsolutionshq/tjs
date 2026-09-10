<?php

namespace App\Http\Controllers\Author;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Submission;
use App\Services\Author\AuthorSubmissionService;
use App\Services\Journal\CallForSubmissionService;
use App\Services\Journal\JournalFeeResolver;
use App\Services\Payments\PaymentFulfillmentService;
use App\Support\SafeHtml;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class SubmissionController extends Controller
{
    public function __construct(
        private CallForSubmissionService $calls,
        private JournalFeeResolver $fees,
        private PaymentFulfillmentService $payments,
        private AuthorSubmissionService $authorSubmissions,
    ) {
    }

    public function index(Request $request): View
    {
        $base = Submission::query()->where('author_id', $request->user()->id);

        $stats = [
            'total' => (clone $base)->count(),
            'in_review' => (clone $base)->whereIn('status', ['submitted', 'under_review', 'resubmitted', 'revision_requested'])->count(),
            'accepted' => (clone $base)->whereIn('status', \App\Support\SubmissionStatus::acceptedStatuses())->count(),
            'rejected' => (clone $base)->where('status', 'rejected')->count(),
        ];

        $query = Submission::query()
            ->with(['journal:id,title,slug', 'issue.volume', 'announcement:id,title'])
            ->where('author_id', $request->user()->id)
            ->orderByDesc('updated_at');

        if ($search = trim((string) $request->get('q'))) {
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                    ->orWhere('keywords', 'like', "%{$search}%")
                    ->orWhere('category', 'like', "%{$search}%")
                    ->orWhereHas('journal', fn ($jq) => $jq->where('title', 'like', "%{$search}%"));
            });
        }

        $status = $request->get('status');
        $statusGroups = [
            'in_review' => ['submitted', 'under_review', 'resubmitted', 'revision_requested'],
            'accepted' => \App\Support\SubmissionStatus::acceptedStatuses(),
            'rejected' => ['rejected'],
        ];
        if (isset($statusGroups[$status])) {
            $query->whereIn('status', $statusGroups[$status]);
        } elseif (in_array($status, array_merge(
            ['submitted', 'under_review', 'resubmitted', 'revision_requested', 'rejected'],
            \App\Support\SubmissionStatus::acceptedStatuses(),
            \App\Support\SubmissionStatus::productionQueueStatuses(),
        ), true)) {
            $query->where('status', $status);
        }

        $submissions = $query->paginate(12)->withQueryString();

        return view('author.submissions.index', compact('submissions', 'stats', 'status'));
    }

    public function create(Request $request): View
    {
        $openCalls = $this->calls->openCalls();
        $preselectedCall = (string) old('announcement_id', $request->integer('announcement') ?: '');

        $journalIds = $openCalls->pluck('journal_id')->unique()->filter()->values();
        $categoriesByJournal = Category::query()
            ->active()
            ->whereIn('journal_id', $journalIds)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get(['id', 'journal_id', 'name'])
            ->groupBy('journal_id')
            ->map(fn ($group) => $group->map(fn (Category $c) => [
                'id' => $c->id,
                'name' => $c->name,
            ])->values())
            ->all();

        $upcomingCalls = $openCalls->isEmpty() ? $this->calls->upcomingCalls() : collect();
        $recentlyClosedCalls = $openCalls->isEmpty() ? $this->calls->recentlyClosedCalls() : collect();

        $feesByJournal = $this->fees->submissionFeesByJournalIds($journalIds->all());
        $issuePublicationFeeByCall = $openCalls->mapWithKeys(function ($call) {
            $fee = $this->fees->requiredPublicationFeeForIssue($call->issue_id);

            return [(string) $call->id => $fee ? [
                'id' => $fee->id,
                'name' => $fee->name,
                'amount' => (int) $fee->amount,
                'currency' => strtoupper((string) $fee->currency),
                'description' => $fee->description,
            ] : null];
        })->all();
        $guidelinesByCall = $openCalls->mapWithKeys(fn ($call) => [
            (string) $call->id => $call->body ? SafeHtml::display($call->body) : '',
        ])->all();

        return view('author.submissions.create', compact(
            'openCalls',
            'preselectedCall',
            'categoriesByJournal',
            'upcomingCalls',
            'recentlyClosedCalls',
            'feesByJournal',
            'issuePublicationFeeByCall',
            'guidelinesByCall',
        ));
    }

    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'announcement_id' => ['required', 'exists:journal_announcements,id'],
        ]);

        $announcement = $this->calls->findOpenCall((int) $request->input('announcement_id'));
        $journal = $announcement->journal;
        abort_unless($journal && $journal->isListed(), 422, 'Journal is not accepting submissions.');

        $data = $request->validate([
            'announcement_id' => ['required', 'exists:journal_announcements,id'],
            'title' => ['required', 'string', 'max:255'],
            'abstract' => ['nullable', 'string'],
            'category' => [
                'nullable',
                'string',
                'max:255',
                Rule::exists('categories', 'name')->where(
                    fn ($query) => $query->where('journal_id', $journal->id)->where('is_active', true)
                ),
            ],
            'keywords' => ['nullable', 'string', 'max:500'],
            'document' => ['required', 'file', 'mimes:doc,docx', 'max:51200'],
        ]);

        $submission = $this->authorSubmissions->create(
            $request->user(),
            $data,
            $request->file('document')
        );

        if ($submission->status === 'fee_pending') {
            try {
                $result = $this->payments->startSubmissionFeePayment($request->user(), $submission);
                if (! empty($result['authorization_url'])) {
                    return redirect()->away($result['authorization_url']);
                }
            } catch (\Throwable $e) {
                return redirect()
                    ->route('author.submissions.show', $submission)
                    ->with('error', $e->getMessage() ?: 'Submission saved but payment could not start. Use Pay fee on the submission page.');
            }
        }

        return redirect()
            ->route('author.submissions.show', $submission)
            ->with('status', $submission->status === 'fee_pending'
                ? 'Submission saved. Complete payment to send it to editors.'
                : 'Submission received.');
    }

    public function show(Request $request, Submission $submission): View
    {
        abort_unless((int) $submission->author_id === (int) $request->user()->id, 403);

        $submission->load(['journal', 'issue.volume', 'announcement', 'journalFee', 'publicationJournalFee', 'timelines.user', 'revisions', 'assignments.reviewer']);

        return view('author.submissions.show', compact('submission'));
    }

    public function resubmit(Request $request, Submission $submission): RedirectResponse
    {
        abort_unless((int) $submission->author_id === (int) $request->user()->id, 403);
        abort_unless(
            in_array($submission->status, ['revision_requested', 'resubmitted'], true),
            422,
            'Revisions are only allowed when revision is requested.'
        );

        $data = $request->validate([
            'document' => ['required', 'file', 'mimes:doc,docx', 'max:51200'],
            'notes' => ['nullable', 'string'],
        ]);

        $this->authorSubmissions->resubmit(
            $request->user(),
            $submission,
            $request->file('document'),
            $data['notes'] ?? null,
        );

        return redirect()
            ->route('author.submissions.show', $submission)
            ->with('status', 'Revision uploaded. It has been returned to the assigned reviewer.');
    }
}
