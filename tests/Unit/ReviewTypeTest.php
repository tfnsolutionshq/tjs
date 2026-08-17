<?php

namespace Tests\Unit;

use App\Models\Journal;
use App\Models\Submission;
use App\Models\User;
use App\Support\ReviewType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class ReviewTypeTest extends TestCase
{
    use RefreshDatabase;

    public function test_submission_inherits_journal_review_type_when_not_set(): void
    {
        $author = User::factory()->create();
        $journal = Journal::query()->create([
            'slug' => 'open-journal',
            'title' => 'Open Journal',
            'is_active' => true,
            'review_type' => ReviewType::OPEN,
        ]);

        $submission = Submission::query()->create([
            'id' => (string) Str::uuid(),
            'journal_id' => $journal->id,
            'author_id' => $author->id,
            'title' => 'Paper',
            'status' => 'submitted',
        ]);

        $this->assertSame(ReviewType::OPEN, ReviewType::forSubmission($submission));
    }

    public function test_submission_override_takes_precedence(): void
    {
        $author = User::factory()->create();
        $journal = Journal::query()->create([
            'slug' => 'open-journal',
            'title' => 'Open Journal',
            'is_active' => true,
            'review_type' => ReviewType::OPEN,
        ]);

        $submission = Submission::query()->create([
            'id' => (string) Str::uuid(),
            'journal_id' => $journal->id,
            'author_id' => $author->id,
            'title' => 'Paper',
            'status' => 'submitted',
            'review_type' => ReviewType::CLOSED,
        ]);

        $this->assertSame(ReviewType::CLOSED, $submission->effectiveReviewType());
    }
}
