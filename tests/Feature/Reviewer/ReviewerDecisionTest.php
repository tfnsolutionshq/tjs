<?php

namespace Tests\Feature\Reviewer;

use App\Models\Journal;
use App\Models\ReviewerAssignment;
use App\Models\Submission;
use App\Models\SubmissionTimeline;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class ReviewerDecisionTest extends TestCase
{
    use RefreshDatabase;

    public function test_reviewer_can_accept_submission_without_rejection_reason(): void
    {
        $reviewer = User::factory()->create(['role' => 'reviewer']);
        $author = User::factory()->create(['role' => 'member']);
        $journal = Journal::query()->create([
            'slug' => 'demo-journal',
            'title' => 'Demo Journal',
            'is_active' => true,
        ]);
        $submission = Submission::query()->create([
            'id' => (string) Str::uuid(),
            'journal_id' => $journal->id,
            'author_id' => $author->id,
            'title' => 'Sample submission',
            'status' => 'under_review',
        ]);
        ReviewerAssignment::query()->create([
            'submission_id' => $submission->id,
            'reviewer_id' => $reviewer->id,
            'status' => 'assigned',
        ]);

        $this->actingAs($reviewer)
            ->post(route('reviewer.reviews.decide', $submission), [
                'decision' => 'accept',
                'comment' => 'Document is correctly formatted.',
            ])
            ->assertRedirect(route('reviewer.reviews.index'))
            ->assertSessionHasNoErrors();

        $submission->refresh();

        $this->assertSame('approved', $submission->status);
        $this->assertSame('Document is correctly formatted.', $submission->review_comment);
        $this->assertNull($submission->rejection_reason);
        $this->assertDatabaseHas('submission_timelines', [
            'submission_id' => $submission->id,
            'event' => 'review_decision',
        ]);
    }

    public function test_reviewer_reject_requires_rejection_reason(): void
    {
        $reviewer = User::factory()->create(['role' => 'reviewer']);
        $author = User::factory()->create(['role' => 'member']);
        $journal = Journal::query()->create([
            'slug' => 'demo-journal',
            'title' => 'Demo Journal',
            'is_active' => true,
        ]);
        $submission = Submission::query()->create([
            'id' => (string) Str::uuid(),
            'journal_id' => $journal->id,
            'author_id' => $author->id,
            'title' => 'Sample submission',
            'status' => 'under_review',
        ]);
        ReviewerAssignment::query()->create([
            'submission_id' => $submission->id,
            'reviewer_id' => $reviewer->id,
            'status' => 'assigned',
        ]);

        $this->actingAs($reviewer)
            ->from(route('reviewer.reviews.show', $submission))
            ->post(route('reviewer.reviews.decide', $submission), [
                'decision' => 'reject',
                'comment' => 'Needs major changes.',
            ])
            ->assertRedirect(route('reviewer.reviews.show', $submission))
            ->assertSessionHasErrors('rejection_reason');
    }

    public function test_reviewer_sees_history_on_show_page(): void
    {
        $reviewer = User::factory()->create(['role' => 'reviewer']);
        $author = User::factory()->create(['role' => 'member']);
        $journal = Journal::query()->create([
            'slug' => 'demo-journal',
            'title' => 'Demo Journal',
            'is_active' => true,
        ]);
        $submission = Submission::query()->create([
            'id' => (string) Str::uuid(),
            'journal_id' => $journal->id,
            'author_id' => $author->id,
            'title' => 'Sample submission',
            'status' => 'resubmitted',
            'document_path' => 'journals/demo/submissions/current.docx',
            'abstract' => 'An abstract for reviewers.',
        ]);
        ReviewerAssignment::query()->create([
            'submission_id' => $submission->id,
            'reviewer_id' => $reviewer->id,
            'status' => 'assigned',
        ]);
        SubmissionTimeline::query()->create([
            'submission_id' => $submission->id,
            'user_id' => $reviewer->id,
            'event' => 'review_decision',
            'metadata' => [
                'decision' => 'revision_requested',
                'comment' => 'Please clarify methods.',
            ],
        ]);

        $this->actingAs($reviewer)
            ->get(route('reviewer.reviews.show', $submission))
            ->assertOk()
            ->assertSee('Full timeline')
            ->assertSee('Revision history')
            ->assertSee('Please clarify methods.')
            ->assertSee('An abstract for reviewers.');
    }
}
