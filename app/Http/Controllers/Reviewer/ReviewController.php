<?php

namespace App\Http\Controllers\Reviewer;

use App\Http\Controllers\Controller;
use App\Models\ReviewerAssignment;
use App\Models\Submission;
use App\Models\SubmissionRevision;
use App\Services\Reviewer\ReviewerDecisionService;
use App\Support\ReviewType;
use App\Support\SubmissionStatus;
use App\Services\Storage\HybridDisk;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReviewController extends Controller
{
    public function __construct(
        private HybridDisk $disks,
        private ReviewerDecisionService $decisions,
    ) {
    }

    public function index(Request $request): View
    {
        $assignments = ReviewerAssignment::query()
            ->with(['submission.journal', 'submission.author'])
            ->where('reviewer_id', $request->user()->id)
            ->whereIn('status', ['assigned', 'in_progress'])
            ->whereHas('submission', fn ($q) => $q->where('status', '!=', SubmissionStatus::FEE_PENDING))
            ->orderBy('priority')
            ->orderBy('due_at')
            ->get();

        return view('reviewer.reviews.index', compact('assignments'));
    }

    public function show(Request $request, Submission $submission): View
    {
        $this->decisions->assertAssigned($request->user(), $submission);

        $submission->load([
            'journal',
            'assignments.reviewer',
            'timelines.user',
            'revisions.uploader',
            'issue.volume',
            'announcement',
        ]);

        $reviewType = ReviewType::forSubmission($submission);
        if (ReviewType::isOpen($reviewType)) {
            $submission->load('author');
        }

        $assignment = $submission->assignments
            ->where('reviewer_id', $request->user()->id)
            ->sortByDesc('id')
            ->first();

        $canDecide = $assignment && in_array($assignment->status, ['assigned', 'in_progress'], true);

        return view('reviewer.reviews.show', compact('submission', 'assignment', 'reviewType', 'canDecide'));
    }

    public function download(Request $request, Submission $submission): StreamedResponse
    {
        $this->decisions->assertAssigned($request->user(), $submission);
        abort_unless($submission->document_path, 404);

        return $this->disks->download(
            $submission->document_path,
            HybridDisk::KIND_DOCUMENTS,
            $submission->document_disk,
            basename($submission->document_path)
        );
    }

    public function downloadRevision(Request $request, Submission $submission, SubmissionRevision $revision): StreamedResponse
    {
        $this->decisions->assertAssigned($request->user(), $submission);
        abort_unless((string) $revision->submission_id === (string) $submission->id, 404);
        abort_unless($revision->document_path, 404);

        return $this->disks->download(
            $revision->document_path,
            HybridDisk::KIND_DOCUMENTS,
            $revision->document_disk,
            basename($revision->document_path)
        );
    }

    public function decide(Request $request, Submission $submission): RedirectResponse
    {
        $data = $request->validate([
            'decision' => ['required', 'in:accept,reject,revision_requested'],
            'comment' => ['nullable', 'string'],
            'rejection_reason' => ['nullable', 'required_if:decision,reject', 'string'],
        ]);

        $this->decisions->decide($request->user(), $submission, $data);

        return redirect()
            ->route('reviewer.reviews.index')
            ->with('status', 'Review decision recorded.');
    }
}
