<?php

namespace App\Http\Controllers\Reviewer;

use App\Http\Controllers\Controller;
use App\Models\ReviewerAssignment;
use App\Models\Submission;
use App\Models\SubmissionRevision;
use App\Models\SubmissionTimeline;
use App\Support\ReviewType;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReviewController extends Controller
{
    public function index(Request $request): View
    {
        $assignments = ReviewerAssignment::query()
            ->with(['submission.journal', 'submission.author'])
            ->where('reviewer_id', $request->user()->id)
            ->whereIn('status', ['assigned', 'in_progress'])
            ->orderBy('priority')
            ->orderBy('due_at')
            ->get();

        return view('reviewer.reviews.index', compact('assignments'));
    }

    public function show(Request $request, Submission $submission): View
    {
        $this->assertAssigned($request, $submission);

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
        $this->assertAssigned($request, $submission);
        abort_unless($submission->document_path && Storage::disk('local')->exists($submission->document_path), 404);

        return Storage::disk('local')->download(
            $submission->document_path,
            basename($submission->document_path)
        );
    }

    public function downloadRevision(Request $request, Submission $submission, SubmissionRevision $revision): StreamedResponse
    {
        $this->assertAssigned($request, $submission);
        abort_unless((string) $revision->submission_id === (string) $submission->id, 404);
        abort_unless($revision->document_path && Storage::disk('local')->exists($revision->document_path), 404);

        return Storage::disk('local')->download(
            $revision->document_path,
            basename($revision->document_path)
        );
    }

    public function decide(Request $request, Submission $submission): RedirectResponse
    {
        $this->assertAssigned($request, $submission);

        $active = ReviewerAssignment::query()
            ->where('submission_id', $submission->id)
            ->where('reviewer_id', $request->user()->id)
            ->whereIn('status', ['assigned', 'in_progress'])
            ->exists();

        abort_unless($active, 422, 'This assignment is not awaiting a decision.');

        $data = $request->validate([
            'decision' => ['required', 'in:accept,reject,revision_requested'],
            'comment' => ['nullable', 'string'],
            'rejection_reason' => ['nullable', 'required_if:decision,reject', 'string'],
        ]);

        DB::transaction(function () use ($request, $submission, $data) {
            $updates = [
                'reviewed_at' => now(),
                'review_comment' => $data['comment'] ?? null,
            ];

            if ($data['decision'] === 'accept') {
                $updates['status'] = 'approved';
                $updates['rejection_reason'] = null;
            } elseif ($data['decision'] === 'reject') {
                $updates['status'] = 'rejected';
                $updates['rejection_reason'] = $data['rejection_reason'] ?? $data['comment'] ?? null;
            } else {
                $updates['status'] = 'revision_requested';
                $updates['review_comment'] = $data['comment'] ?? null;
            }

            $submission->update($updates);

            ReviewerAssignment::query()
                ->where('submission_id', $submission->id)
                ->where('reviewer_id', $request->user()->id)
                ->whereIn('status', ['assigned', 'in_progress'])
                ->update(['status' => 'completed']);

            SubmissionTimeline::query()->create([
                'submission_id' => $submission->id,
                'user_id' => $request->user()->id,
                'event' => 'review_decision',
                'metadata' => [
                    'decision' => $data['decision'],
                    'comment' => $data['comment'] ?? null,
                    'rejection_reason' => $updates['rejection_reason'] ?? null,
                ],
            ]);
        });

        return redirect()
            ->route('reviewer.reviews.index')
            ->with('status', 'Review decision recorded.');
    }

    private function assertAssigned(Request $request, Submission $submission): void
    {
        $assigned = ReviewerAssignment::query()
            ->where('submission_id', $submission->id)
            ->where('reviewer_id', $request->user()->id)
            ->exists();

        abort_unless(
            $assigned || (int) $submission->reviewer_id === (int) $request->user()->id,
            403
        );
    }
}
