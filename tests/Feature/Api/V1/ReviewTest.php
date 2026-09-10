<?php

namespace Tests\Feature\Api\V1;

use App\Models\Journal;
use App\Models\ReviewerAssignment;
use App\Models\Submission;
use App\Models\SubmissionTimeline;
use App\Models\User;
use App\Support\ReviewType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ReviewTest extends TestCase
{
    use RefreshDatabase;

    public function test_reviewer_can_list_active_assignments(): void
    {
        [$submission, $reviewer] = $this->seedAssignment();

        Sanctum::actingAs($reviewer);

        $this->getJson('/api/v1/me/reviews')
            ->assertOk()
            ->assertJsonPath('meta.stats.pending', 1)
            ->assertJsonPath('data.0.submission.id', $submission->id);
    }

    public function test_reviewer_can_view_assignment_detail(): void
    {
        [$submission, $reviewer] = $this->seedAssignment(withTimeline: true);

        Sanctum::actingAs($reviewer);

        $this->getJson('/api/v1/me/reviews/'.$submission->id)
            ->assertOk()
            ->assertJsonPath('data.id', $submission->id)
            ->assertJsonPath('data.can_decide', true)
            ->assertJsonStructure(['data' => ['timeline', 'revisions', 'assignment']]);
    }

    public function test_reviewer_can_accept_via_api(): void
    {
        [$submission, $reviewer] = $this->seedAssignment();

        Sanctum::actingAs($reviewer);

        $this->postJson('/api/v1/me/reviews/'.$submission->id.'/decide', [
            'decision' => 'accept',
            'comment' => 'Well written.',
        ])
            ->assertOk()
            ->assertJsonPath('data.submission.status', 'ready_for_production');

        $this->assertDatabaseHas('submission_timelines', [
            'submission_id' => $submission->id,
            'event' => 'review_decision',
        ]);
    }

    public function test_reviewer_reject_requires_rejection_reason(): void
    {
        [$submission, $reviewer] = $this->seedAssignment();

        Sanctum::actingAs($reviewer);

        $this->postJson('/api/v1/me/reviews/'.$submission->id.'/decide', [
            'decision' => 'reject',
            'comment' => 'Not suitable.',
        ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('rejection_reason');
    }

    public function test_closed_review_hides_author_in_detail(): void
    {
        [$submission, $reviewer] = $this->seedAssignment(reviewType: ReviewType::CLOSED);

        Sanctum::actingAs($reviewer);

        $this->getJson('/api/v1/me/reviews/'.$submission->id)
            ->assertOk()
            ->assertJsonMissing(['author' => ['id' => $submission->author_id]]);
    }

    public function test_open_review_shows_author_in_detail(): void
    {
        [$submission, $reviewer] = $this->seedAssignment(reviewType: ReviewType::OPEN);

        Sanctum::actingAs($reviewer);

        $this->getJson('/api/v1/me/reviews/'.$submission->id)
            ->assertOk()
            ->assertJsonPath('data.author.id', $submission->author_id);
    }

    public function test_non_reviewer_cannot_access_review_queue(): void
    {
        $member = User::factory()->create(['email_verified_at' => now()]);

        Sanctum::actingAs($member);

        $this->getJson('/api/v1/me/reviews')->assertForbidden();
    }

    /**
     * @return array{0: Submission, 1: User}
     */
    private function seedAssignment(
        ?string $reviewType = null,
        bool $withTimeline = false,
    ): array {
        $reviewer = User::factory()->create(['role' => 'reviewer', 'email_verified_at' => now()]);
        $author = User::factory()->create(['role' => 'member']);
        $journal = Journal::query()->create([
            'slug' => 'review-api-journal',
            'title' => 'Review API Journal',
            'is_active' => true,
            'review_type' => $reviewType ?? ReviewType::CLOSED,
        ]);
        $submission = Submission::query()->create([
            'id' => (string) Str::uuid(),
            'journal_id' => $journal->id,
            'author_id' => $author->id,
            'title' => 'API review paper',
            'status' => 'under_review',
            'abstract' => 'Abstract for reviewers.',
            'document_path' => 'journals/review-api-journal/submissions/test.docx',
            'document_disk' => 'local',
            'review_type' => $reviewType ?? ReviewType::CLOSED,
        ]);
        ReviewerAssignment::query()->create([
            'submission_id' => $submission->id,
            'reviewer_id' => $reviewer->id,
            'status' => 'assigned',
        ]);

        if ($withTimeline) {
            SubmissionTimeline::query()->create([
                'submission_id' => $submission->id,
                'user_id' => $reviewer->id,
                'event' => 'review_decision',
                'metadata' => ['decision' => 'revision_requested', 'comment' => 'Clarify methods.'],
            ]);
        }

        return [$submission, $reviewer];
    }
}
