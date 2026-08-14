<?php

namespace App\Http\Controllers\Author;

use App\Http\Controllers\Controller;
use App\Models\Journal;
use App\Models\Submission;
use App\Models\SubmissionRevision;
use App\Models\SubmissionTimeline;
use App\Services\Storage\ArticleStorage;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\View\View;

class SubmissionController extends Controller
{
    public function __construct(
        private ArticleStorage $storage,
    ) {
    }

    public function index(Request $request): View
    {
        $base = Submission::query()->where('author_id', $request->user()->id);

        $stats = [
            'total' => (clone $base)->count(),
            'in_review' => (clone $base)->whereIn('status', ['submitted', 'under_review', 'resubmitted', 'revision_requested'])->count(),
            'accepted' => (clone $base)->where('status', 'accepted')->count(),
            'rejected' => (clone $base)->where('status', 'rejected')->count(),
        ];

        $query = Submission::query()
            ->with('journal:id,title,slug')
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
            'accepted' => ['accepted'],
            'rejected' => ['rejected'],
        ];
        if (isset($statusGroups[$status])) {
            $query->whereIn('status', $statusGroups[$status]);
        } elseif (in_array($status, ['submitted', 'under_review', 'resubmitted', 'revision_requested', 'accepted', 'rejected'], true)) {
            $query->where('status', $status);
        }

        $submissions = $query->paginate(12)->withQueryString();

        return view('author.submissions.index', compact('submissions', 'stats', 'status'));
    }

    public function create(): View
    {
        $journals = Journal::query()
            ->where('is_active', true)
            ->orderBy('title')
            ->get(['id', 'title', 'slug', 'subtitle']);

        return view('author.submissions.create', compact('journals'));
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'journal_id' => ['required', 'exists:journals,id'],
            'title' => ['required', 'string', 'max:255'],
            'abstract' => ['nullable', 'string'],
            'category' => ['nullable', 'string', 'max:255'],
            'keywords' => ['nullable', 'string', 'max:500'],
            'document' => ['required', 'file', 'mimes:pdf,doc,docx', 'max:51200'],
        ]);

        $journal = Journal::query()->findOrFail($data['journal_id']);
        abort_unless($journal->is_active, 422, 'Journal is not accepting submissions.');

        $submission = DB::transaction(function () use ($request, $data, $journal) {
            $submission = new Submission([
                'journal_id' => $journal->id,
                'author_id' => $request->user()->id,
                'title' => $data['title'],
                'abstract' => $data['abstract'] ?? null,
                'category' => $data['category'] ?? null,
                'keywords' => $data['keywords'] ?? null,
                'status' => 'submitted',
            ]);
            $submission->id = (string) Str::uuid();

            $path = $this->storage->storeSubmissionDocument(
                $journal,
                $submission->id,
                $request->file('document')
            );
            $submission->document_path = $path;
            $submission->save();

            SubmissionTimeline::query()->create([
                'submission_id' => $submission->id,
                'user_id' => $request->user()->id,
                'event' => 'submitted',
                'metadata' => null,
            ]);

            return $submission;
        });

        return redirect()
            ->route('author.submissions.show', $submission)
            ->with('status', 'Submission received.');
    }

    public function show(Request $request, Submission $submission): View
    {
        abort_unless((int) $submission->author_id === (int) $request->user()->id, 403);

        $submission->load(['journal', 'timelines.user', 'revisions', 'assignments.reviewer']);

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
            'document' => ['required', 'file', 'mimes:pdf,doc,docx', 'max:51200'],
            'notes' => ['nullable', 'string'],
        ]);

        $submission->loadMissing('journal');

        DB::transaction(function () use ($request, $submission, $data) {
            $path = $this->storage->storeSubmissionDocument(
                $submission->journal,
                $submission->id,
                $request->file('document')
            );

            $revisionNumber = (int) $submission->revisions()->max('revision_number') + 1;

            SubmissionRevision::query()->create([
                'submission_id' => $submission->id,
                'uploaded_by' => $request->user()->id,
                'revision_number' => $revisionNumber,
                'document_path' => $path,
                'notes' => $data['notes'] ?? null,
            ]);

            $submission->update([
                'document_path' => $path,
                'status' => 'resubmitted',
            ]);

            SubmissionTimeline::query()->create([
                'submission_id' => $submission->id,
                'user_id' => $request->user()->id,
                'event' => 'resubmitted',
                'metadata' => [
                    'revision_number' => $revisionNumber,
                ],
            ]);
        });

        return redirect()
            ->route('author.submissions.show', $submission)
            ->with('status', 'Revision uploaded.');
    }
}
