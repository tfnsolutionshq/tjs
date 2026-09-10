<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\OpenCallResource;
use App\Http\Resources\Api\V1\SubmissionDetailResource;
use App\Http\Resources\Api\V1\SubmissionResource;
use App\Models\Category;
use App\Models\Submission;
use App\Services\Author\AuthorSubmissionService;
use App\Services\Journal\CallForSubmissionService;
use App\Services\Journal\JournalFeeResolver;
use App\Services\Payments\JournalPaymentUnavailableException;
use App\Services\Payments\PaymentFulfillmentService;
use App\Support\Api\PaymentInitializePayload;
use App\Support\SubmissionStatus;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class SubmissionController extends Controller
{
    public function __construct(
        private CallForSubmissionService $calls,
        private JournalFeeResolver $fees,
        private AuthorSubmissionService $submissions,
        private PaymentFulfillmentService $payments,
    ) {
    }

    public function createOptions(Request $request): JsonResponse
    {
        $openCalls = $this->calls->openCalls();
        $journalIds = $openCalls->pluck('journal_id')->unique()->filter()->values();

        $openCallsPayload = $openCalls->map(function ($call) {
            $fee = $this->fees->requiredSubmissionFeeForJournal((int) $call->journal_id);
            $publicationFee = $this->fees->requiredPublicationFeeForIssue($call->issue_id);

            return array_merge(
                (new OpenCallResource($call))->resolve(),
                [
                    'submission_fee' => $fee ? [
                        'id' => $fee->id,
                        'name' => $fee->name,
                        'amount' => (int) $fee->amount,
                        'currency' => strtoupper((string) $fee->currency),
                        'description' => $fee->description,
                    ] : null,
                    'publication_fee' => $publicationFee ? [
                        'id' => $publicationFee->id,
                        'name' => $publicationFee->name,
                        'amount' => (int) $publicationFee->amount,
                        'currency' => strtoupper((string) $publicationFee->currency),
                        'description' => $publicationFee->description,
                    ] : null,
                ]
            );
        })->values();

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

        return response()->json([
            'data' => [
                'open_calls' => $openCallsPayload,
                'upcoming_calls' => OpenCallResource::collection(
                    $openCalls->isEmpty() ? $this->calls->upcomingCalls() : collect()
                ),
                'recently_closed_calls' => OpenCallResource::collection(
                    $openCalls->isEmpty() ? $this->calls->recentlyClosedCalls() : collect()
                ),
                'categories_by_journal' => $categoriesByJournal,
            ],
        ]);
    }

    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        $base = Submission::query()->where('author_id', $user->id);

        $stats = [
            'total' => (clone $base)->count(),
            'in_review' => (clone $base)->whereIn('status', ['submitted', 'under_review', 'resubmitted', 'revision_requested'])->count(),
            'accepted' => (clone $base)->whereIn('status', SubmissionStatus::acceptedStatuses())->count(),
            'rejected' => (clone $base)->where('status', 'rejected')->count(),
        ];

        $query = Submission::query()
            ->with(['journal:id,title,slug', 'issue.volume', 'announcement:id,title'])
            ->where('author_id', $user->id)
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
            'accepted' => SubmissionStatus::acceptedStatuses(),
            'rejected' => ['rejected'],
        ];
        if (isset($statusGroups[$status])) {
            $query->whereIn('status', $statusGroups[$status]);
        } elseif (in_array($status, array_merge(
            ['submitted', 'under_review', 'resubmitted', 'revision_requested', 'rejected'],
            SubmissionStatus::acceptedStatuses(),
            SubmissionStatus::productionQueueStatuses(),
        ), true)) {
            $query->where('status', $status);
        }

        $submissions = $query->paginate($request->integer('per_page', 12))->withQueryString();

        return SubmissionResource::collection($submissions)
            ->additional(['meta' => ['stats' => $stats]])
            ->response();
    }

    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'announcement_id' => ['required', 'exists:journal_announcements,id'],
        ]);

        $announcement = $this->calls->findOpenCall((int) $request->input('announcement_id'));
        $journal = $announcement->journal;

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

        $submission = $this->submissions->create($request->user(), $data, $request->file('document'));

        $payload = [
            'submission' => new SubmissionDetailResource($submission),
            'message' => $submission->status === 'fee_pending'
                ? 'Submission saved. Complete payment to send it to editors.'
                : 'Submission received.',
        ];

        if ($submission->status === 'fee_pending') {
            try {
                $payment = $this->payments->startSubmissionFeePayment($request->user(), $submission);
                $payload['payment'] = PaymentInitializePayload::fromPaystackResult($payment);
            } catch (\Throwable $e) {
                $payload['payment_error'] = $e->getMessage() ?: 'Submission saved but payment could not start.';
            }
        }

        return response()->json(['data' => $payload], 201);
    }

    public function show(Request $request, Submission $submission): JsonResponse
    {
        $this->submissions->assertAuthorOwns($request->user(), $submission);

        $submission->load(['journal', 'issue.volume', 'announcement', 'journalFee', 'publicationJournalFee', 'timelines.user', 'revisions']);

        return response()->json([
            'data' => new SubmissionDetailResource($submission),
        ]);
    }

    public function resubmit(Request $request, Submission $submission): JsonResponse
    {
        $data = $request->validate([
            'document' => ['required', 'file', 'mimes:doc,docx', 'max:51200'],
            'notes' => ['nullable', 'string'],
        ]);

        $submission = $this->submissions->resubmit(
            $request->user(),
            $submission,
            $request->file('document'),
            $data['notes'] ?? null,
        );

        return response()->json([
            'data' => [
                'submission' => new SubmissionDetailResource($submission),
                'message' => 'Revision uploaded. It has been returned to the assigned reviewer.',
            ],
        ]);
    }

    public function paySubmissionFee(Request $request, Submission $submission): JsonResponse
    {
        $this->submissions->assertAuthorOwns($request->user(), $submission);

        try {
            $result = $this->payments->startSubmissionFeePayment($request->user(), $submission);
        } catch (JournalPaymentUnavailableException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        } catch (\Throwable $e) {
            return response()->json([
                'message' => $e->getMessage() ?: 'Unable to start payment. Check Paystack configuration.',
            ], 422);
        }

        if (empty($result['authorization_url'])) {
            return response()->json([
                'message' => 'Unable to start payment with Paystack. Confirm PAYSTACK_SECRET_KEY is set.',
            ], 422);
        }

        return response()->json([
            'data' => PaymentInitializePayload::fromPaystackResult($result),
        ]);
    }

    public function payPublicationFee(Request $request, Submission $submission): JsonResponse
    {
        $this->submissions->assertAuthorOwns($request->user(), $submission);

        try {
            $result = $this->payments->startPublicationFeePayment($request->user(), $submission);
        } catch (JournalPaymentUnavailableException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        } catch (\Throwable $e) {
            return response()->json([
                'message' => $e->getMessage() ?: 'Unable to start payment. Check Paystack configuration.',
            ], 422);
        }

        if (empty($result['authorization_url'])) {
            return response()->json([
                'message' => 'Unable to start payment with Paystack. Confirm PAYSTACK_SECRET_KEY is set.',
            ], 422);
        }

        return response()->json([
            'data' => PaymentInitializePayload::fromPaystackResult($result),
        ]);
    }
}
