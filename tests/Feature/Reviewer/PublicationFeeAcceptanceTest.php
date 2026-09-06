<?php

namespace Tests\Feature\Reviewer;

use App\Models\Issue;
use App\Models\Journal;
use App\Models\JournalFee;
use App\Models\ReviewerAssignment;
use App\Models\Submission;
use App\Models\User;
use App\Models\Volume;
use App\Notifications\PublicationFeeDueNotification;
use App\Support\JournalFeePurpose;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;
use Tests\TestCase;

class PublicationFeeAcceptanceTest extends TestCase
{
    use RefreshDatabase;

    public function test_acceptance_with_issue_publication_fee_sets_pending_and_emails_author(): void
    {
        Notification::fake();

        $reviewer = User::factory()->create(['role' => 'reviewer']);
        $author = User::factory()->create(['role' => 'member']);
        $journal = Journal::query()->create([
            'slug' => 'apc-journal',
            'title' => 'APC Journal',
            'is_active' => true,
        ]);
        $volume = Volume::query()->create([
            'journal_id' => $journal->id,
            'volume_number' => 1,
            'year' => 2026,
            'status' => 'published',
        ]);
        $publicationFee = JournalFee::query()->create([
            'journal_id' => $journal->id,
            'name' => 'Issue APC',
            'purpose' => JournalFeePurpose::ARTICLE,
            'amount' => 25000,
            'currency' => 'NGN',
            'is_active' => true,
        ]);
        $issue = Issue::query()->create([
            'volume_id' => $volume->id,
            'issue_number' => 1,
            'status' => 'draft',
            'journal_fee_id' => $publicationFee->id,
        ]);
        $submission = Submission::query()->create([
            'id' => (string) Str::uuid(),
            'journal_id' => $journal->id,
            'issue_id' => $issue->id,
            'author_id' => $author->id,
            'title' => 'Accepted manuscript',
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
                'comment' => 'Well written.',
            ])
            ->assertRedirect(route('reviewer.reviews.index'));

        $submission->refresh();

        $this->assertSame('publication_fee_pending', $submission->status);
        $this->assertSame($publicationFee->id, $submission->publication_journal_fee_id);

        Notification::assertSentTo($author, PublicationFeeDueNotification::class);
    }

    public function test_acceptance_without_issue_publication_fee_goes_straight_to_approved(): void
    {
        Notification::fake();

        $reviewer = User::factory()->create(['role' => 'reviewer']);
        $author = User::factory()->create(['role' => 'member']);
        $journal = Journal::query()->create([
            'slug' => 'free-pub-journal',
            'title' => 'Free Pub Journal',
            'is_active' => true,
        ]);
        $volume = Volume::query()->create([
            'journal_id' => $journal->id,
            'volume_number' => 1,
            'year' => 2026,
            'status' => 'published',
        ]);
        $issue = Issue::query()->create([
            'volume_id' => $volume->id,
            'issue_number' => 1,
            'status' => 'draft',
        ]);
        $submission = Submission::query()->create([
            'id' => (string) Str::uuid(),
            'journal_id' => $journal->id,
            'issue_id' => $issue->id,
            'author_id' => $author->id,
            'title' => 'Free publication manuscript',
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
            ])
            ->assertRedirect(route('reviewer.reviews.index'));

        $submission->refresh();

        $this->assertSame('ready_for_production', $submission->status);
        $this->assertNull($submission->publication_journal_fee_id);
        Notification::assertNothingSent();
    }

    public function test_author_sees_publication_fee_prompt_on_submission_page(): void
    {
        $author = User::factory()->create(['role' => 'member']);
        $journal = Journal::query()->create([
            'slug' => 'prompt-journal',
            'title' => 'Prompt Journal',
            'is_active' => true,
        ]);
        $fee = JournalFee::query()->create([
            'journal_id' => $journal->id,
            'name' => 'APC',
            'purpose' => JournalFeePurpose::ARTICLE,
            'amount' => 10000,
            'currency' => 'NGN',
            'is_active' => true,
        ]);
        $submission = Submission::query()->create([
            'id' => (string) Str::uuid(),
            'journal_id' => $journal->id,
            'author_id' => $author->id,
            'title' => 'Awaiting APC',
            'status' => 'publication_fee_pending',
            'publication_journal_fee_id' => $fee->id,
        ]);

        $this->actingAs($author)
            ->get(route('author.submissions.show', $submission))
            ->assertOk()
            ->assertSee('Accepted — publication fee due')
            ->assertSee('Pay publication fee');
    }
}
