<?php

namespace Tests\Feature\Api\V1;

use App\Models\Issue;
use App\Models\Journal;
use App\Models\JournalReviewerRequest;
use App\Models\Submission;
use App\Models\SubmissionProductionFile;
use App\Models\User;
use App\Models\Volume;
use App\Support\JournalActivation;
use App\Support\JournalTeamRoles;
use App\Support\SubmissionStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class JournalManageTest extends TestCase
{
    use RefreshDatabase;

    public function test_editor_can_view_dashboard_and_submissions(): void
    {
        [$journal, $editor, $submission] = $this->seedEditorSubmission();

        Sanctum::actingAs($editor);

        $this->getJson('/api/v1/me/journals/'.$journal->slug.'/manage/dashboard')
            ->assertOk()
            ->assertJsonPath('data.stats.submissions', 1)
            ->assertJsonPath('data.journal.can_mutate', true);

        $this->getJson('/api/v1/me/journals/'.$journal->slug.'/manage/submissions')
            ->assertOk()
            ->assertJsonPath('meta.stats.total', 1)
            ->assertJsonPath('data.0.id', $submission->id);
    }

    public function test_editor_can_assign_reviewer_via_api(): void
    {
        [$journal, $editor, $submission] = $this->seedEditorSubmission();
        $reviewer = User::factory()->create(['role' => 'reviewer', 'email_verified_at' => now()]);

        Sanctum::actingAs($editor);

        $this->postJson('/api/v1/me/journals/'.$journal->slug.'/manage/submissions/'.$submission->id.'/assign-reviewer', [
            'reviewer_id' => $reviewer->id,
            'priority' => 2,
        ])
            ->assertOk()
            ->assertJsonPath('data.submission.status', 'under_review')
            ->assertJsonPath('data.submission.reviewer.id', $reviewer->id);

        $this->assertDatabaseHas('reviewer_assignments', [
            'submission_id' => $submission->id,
            'reviewer_id' => $reviewer->id,
            'status' => 'assigned',
        ]);
    }

    public function test_editor_can_publish_ready_submission(): void
    {
        [$journal, $editor, $submission, $issue] = $this->seedReadyToPublish();

        Sanctum::actingAs($editor);

        $this->postJson('/api/v1/me/journals/'.$journal->slug.'/manage/submissions/'.$submission->id.'/publish', [
            'issue_id' => $issue->id,
            'visibility' => 'open',
        ])
            ->assertOk()
            ->assertJsonPath('data.submission.status', SubmissionStatus::PUBLISHED)
            ->assertJsonPath('data.article.title', $submission->title);

        $this->assertDatabaseHas('articles', [
            'submission_id' => $submission->id,
            'journal_id' => $journal->id,
            'status' => 'published',
        ]);
    }

    public function test_non_manager_cannot_access_journal_manage_api(): void
    {
        [$journal, , $submission] = $this->seedEditorSubmission();
        $member = User::factory()->create(['email_verified_at' => now()]);

        Sanctum::actingAs($member);

        $this->getJson('/api/v1/me/journals/'.$journal->slug.'/manage/dashboard')->assertForbidden();
        $this->getJson('/api/v1/me/journals/'.$journal->slug.'/manage/submissions/'.$submission->id)->assertForbidden();
    }

    public function test_activation_locked_journal_blocks_submissions_but_allows_dashboard(): void
    {
        $editor = User::factory()->create(['email_verified_at' => now()]);
        $journal = Journal::query()->create([
            'slug' => 'locked-journal',
            'title' => 'Locked Journal',
            'is_active' => true,
            'activation_status' => JournalActivation::STATUS_UNPAID,
        ]);
        $journal->assignTeamMember($editor, JournalTeamRoles::EDITOR);

        Sanctum::actingAs($editor);

        $this->getJson('/api/v1/me/journals/'.$journal->slug.'/manage/dashboard')
            ->assertOk()
            ->assertJsonPath('data.journal.activation_locked', true);

        $this->getJson('/api/v1/me/journals/'.$journal->slug.'/manage/submissions')
            ->assertForbidden()
            ->assertJsonPath('error', 'activation_locked');
    }

    public function test_editor_can_list_and_approve_reviewer_request(): void
    {
        $editor = User::factory()->create(['email_verified_at' => now()]);
        $applicant = User::factory()->create(['email_verified_at' => now()]);
        $journal = Journal::query()->create([
            'slug' => 'reviewer-req-journal',
            'title' => 'Reviewer Req Journal',
            'is_active' => true,
            'activation_status' => JournalActivation::STATUS_ACTIVE,
        ]);
        $journal->assignTeamMember($editor, JournalTeamRoles::ADMIN);

        $request = JournalReviewerRequest::query()->create([
            'journal_id' => $journal->id,
            'user_id' => $applicant->id,
            'status' => JournalReviewerRequest::STATUS_PENDING,
            'message' => 'I would like to review.',
        ]);

        Sanctum::actingAs($editor);

        $this->getJson('/api/v1/me/journals/'.$journal->slug.'/manage/reviewer-requests')
            ->assertOk()
            ->assertJsonPath('meta.stats.pending', 1)
            ->assertJsonPath('data.pending.0.id', $request->id);

        $this->postJson('/api/v1/me/journals/'.$journal->slug.'/manage/reviewer-requests/'.$request->id.'/approve')
            ->assertOk()
            ->assertJsonPath('data.request.status', JournalReviewerRequest::STATUS_APPROVED);
    }

    public function test_form_options_lists_reviewers_and_issues(): void
    {
        [$journal, $editor] = array_slice($this->seedEditorSubmission(), 0, 2);
        $reviewer = User::factory()->create(['role' => 'reviewer']);
        $journal->assignTeamMember($reviewer, JournalTeamRoles::REVIEWER);

        Sanctum::actingAs($editor);

        $this->getJson('/api/v1/me/journals/'.$journal->slug.'/manage/submissions/form-options')
            ->assertOk()
            ->assertJsonStructure(['data' => ['reviewers', 'issues', 'review_types', 'visibility_options']]);
    }

    /**
     * @return array{0: Journal, 1: User, 2: Submission}
     */
    private function seedEditorSubmission(): array
    {
        $editor = User::factory()->create(['email_verified_at' => now()]);
        $author = User::factory()->create(['role' => 'member']);
        $journal = Journal::query()->create([
            'slug' => 'manage-api-journal',
            'title' => 'Manage API Journal',
            'is_active' => true,
            'activation_status' => JournalActivation::STATUS_ACTIVE,
        ]);
        $journal->assignTeamMember($editor, JournalTeamRoles::EDITOR);

        $submission = Submission::query()->create([
            'id' => (string) Str::uuid(),
            'journal_id' => $journal->id,
            'author_id' => $author->id,
            'title' => 'Manage API submission',
            'status' => 'submitted',
            'document_path' => 'journals/manage-api-journal/submissions/test.docx',
            'document_disk' => 'local',
        ]);

        return [$journal, $editor, $submission];
    }

    /**
     * @return array{0: Journal, 1: User, 2: Submission, 3: Issue}
     */
    private function seedReadyToPublish(): array
    {
        $editor = User::factory()->create(['email_verified_at' => now()]);
        $author = User::factory()->create(['role' => 'member']);
        $journal = Journal::query()->create([
            'slug' => 'publish-api-journal',
            'title' => 'Publish API Journal',
            'is_active' => true,
            'activation_status' => JournalActivation::STATUS_ACTIVE,
        ]);
        $journal->assignTeamMember($editor, JournalTeamRoles::EDITOR);

        $volume = Volume::query()->create([
            'journal_id' => $journal->id,
            'volume_number' => 1,
            'year' => 2026,
            'status' => 'published',
        ]);
        $issue = Issue::query()->create([
            'volume_id' => $volume->id,
            'issue_number' => 1,
            'status' => 'published',
        ]);

        $submission = Submission::query()->create([
            'id' => (string) Str::uuid(),
            'journal_id' => $journal->id,
            'issue_id' => $issue->id,
            'author_id' => $author->id,
            'title' => 'Ready to publish paper',
            'status' => SubmissionStatus::READY_TO_PUBLISH,
            'document_path' => 'journals/publish-api-journal/submissions/original.docx',
            'document_disk' => 'local',
            'production_completed_at' => now(),
        ]);

        SubmissionProductionFile::query()->create([
            'submission_id' => $submission->id,
            'uploaded_by' => $editor->id,
            'version' => 1,
            'document_path' => 'journals/publish-api-journal/submissions/production.docx',
            'document_disk' => 'local',
            'original_filename' => 'production.docx',
            'is_current' => true,
        ]);

        return [$journal, $editor, $submission, $issue];
    }
}
