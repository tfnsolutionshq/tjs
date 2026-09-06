<?php

namespace Tests\Feature\Admin;

use App\Models\Journal;
use App\Models\JournalAnnouncement;
use App\Models\JournalFee;
use App\Models\Submission;
use App\Models\User;
use App\Support\JournalActivation;
use App\Support\JournalFeePurpose;
use App\Support\JournalTeamRoles;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class FeePendingSubmissionTest extends TestCase
{
    use RefreshDatabase;

    public function test_platform_admin_can_view_fee_pending_submission(): void
    {
        [$submission] = $this->seedFeePendingSubmission();

        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)
            ->get(route('admin.submissions.show', $submission))
            ->assertOk()
            ->assertSee('Submission fee pending')
            ->assertDontSee('Assign reviewer');
    }

    public function test_platform_admin_cannot_assign_reviewer_while_fee_pending(): void
    {
        [$submission, $journal] = $this->seedFeePendingSubmission();

        $admin = User::factory()->create(['role' => 'admin']);
        $reviewer = User::factory()->create(['role' => 'reviewer']);
        $journal->assignTeamMember($reviewer, JournalTeamRoles::REVIEWER);

        $this->actingAs($admin)
            ->post(route('admin.submissions.assign-reviewer', $submission), [
                'reviewer_id' => $reviewer->id,
            ])
            ->assertStatus(422);

        $submission->refresh();
        $this->assertSame('fee_pending', $submission->status);
        $this->assertNull($submission->reviewer_id);
    }

    public function test_journal_manager_cannot_update_review_type_while_fee_pending(): void
    {
        [$submission, $journal] = $this->seedFeePendingSubmission();

        $manager = User::factory()->create();
        $journal->assignTeamMember($manager, JournalTeamRoles::ADMIN);

        $this->actingAs($manager)
            ->post(route('journal.manage.submissions.update-review-type', [$journal, $submission]), [
                'review_type' => 'open',
            ])
            ->assertStatus(422);

        $submission->refresh();
        $this->assertSame('single_blind', $submission->review_type);
    }

    /**
     * @return array{0: Submission, 1: Journal}
     */
    private function seedFeePendingSubmission(): array
    {
        $journal = Journal::query()->create([
            'slug' => 'fee-pending-journal',
            'title' => 'Fee Pending Journal',
            'is_active' => true,
            'activation_status' => JournalActivation::STATUS_ACTIVE,
        ]);

        $fee = JournalFee::query()->create([
            'journal_id' => $journal->id,
            'name' => 'Submission fee',
            'purpose' => JournalFeePurpose::SUBMISSION,
            'amount' => 5000,
            'currency' => 'NGN',
            'is_active' => true,
        ]);

        $author = User::factory()->create();

        $announcement = JournalAnnouncement::query()->create([
            'journal_id' => $journal->id,
            'title' => 'Open call',
            'body' => 'Submit now',
            'type' => 'call_for_submissions',
            'is_published' => true,
            'closes_at' => now()->addWeek(),
        ]);

        $submission = Submission::query()->create([
            'id' => (string) Str::uuid(),
            'journal_id' => $journal->id,
            'announcement_id' => $announcement->id,
            'journal_fee_id' => $fee->id,
            'author_id' => $author->id,
            'title' => 'Awaiting payment',
            'status' => 'fee_pending',
            'document_path' => 'submissions/test.docx',
            'document_disk' => 'local',
            'review_type' => 'single_blind',
        ]);

        return [$submission, $journal];
    }
}
