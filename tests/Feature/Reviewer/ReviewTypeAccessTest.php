<?php

namespace Tests\Feature\Reviewer;

use App\Models\Journal;
use App\Models\ReviewerAssignment;
use App\Models\Submission;
use App\Models\User;
use App\Support\ReviewType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class ReviewTypeAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_closed_review_hides_author_from_reviewer(): void
    {
        [$reviewer, $author, $submission] = $this->seedAssignment(ReviewType::CLOSED);

        $this->actingAs($reviewer)
            ->get(route('reviewer.reviews.show', $submission))
            ->assertOk()
            ->assertSee('Author identity withheld for closed review')
            ->assertDontSee($author->name);
    }

    public function test_open_review_shows_author_to_reviewer(): void
    {
        [$reviewer, $author, $submission] = $this->seedAssignment(ReviewType::OPEN);

        $this->actingAs($reviewer)
            ->get(route('reviewer.reviews.show', $submission))
            ->assertOk()
            ->assertSee('Author: '.$author->name);
    }

    /**
     * @return array{0: User, 1: User, 2: Submission}
     */
    private function seedAssignment(string $reviewType): array
    {
        $reviewer = User::factory()->create(['role' => 'reviewer']);
        $author = User::factory()->create(['role' => 'member', 'name' => 'Hidden Author Name']);
        $journal = Journal::query()->create([
            'slug' => 'demo-journal',
            'title' => 'Demo Journal',
            'is_active' => true,
            'review_type' => $reviewType,
        ]);
        $submission = Submission::query()->create([
            'id' => (string) Str::uuid(),
            'journal_id' => $journal->id,
            'author_id' => $author->id,
            'title' => 'Sample submission',
            'status' => 'under_review',
            'review_type' => $reviewType,
        ]);
        ReviewerAssignment::query()->create([
            'submission_id' => $submission->id,
            'reviewer_id' => $reviewer->id,
            'status' => 'assigned',
        ]);

        return [$reviewer, $author, $submission];
    }
}
