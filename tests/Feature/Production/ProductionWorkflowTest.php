<?php

namespace Tests\Feature\Production;

use App\Models\Issue;
use App\Models\Journal;
use App\Models\ReviewerAssignment;
use App\Models\Submission;
use App\Models\User;
use App\Models\Volume;
use App\Notifications\ReadyForProductionNotification;
use App\Support\JournalTeamRoles;
use App\Support\SubmissionStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Tests\TestCase;

class ProductionWorkflowTest extends TestCase
{
    use RefreshDatabase;

    public function test_acceptance_moves_submission_to_ready_for_production(): void
    {
        Notification::fake();

        [$journal, $submission, $reviewer] = $this->seedAcceptedSubmission(withProductionEditor: true);

        $this->actingAs($reviewer)
            ->post(route('reviewer.reviews.decide', $submission), [
                'decision' => 'accept',
                'comment' => 'Accepted.',
            ])
            ->assertRedirect(route('reviewer.reviews.index'));

        $submission->refresh();
        $this->assertSame(SubmissionStatus::READY_FOR_PRODUCTION, $submission->status);

        $productionEditor = User::query()->where('email', 'production@example.com')->first();
        Notification::assertSentTo($productionEditor, ReadyForProductionNotification::class);
    }

    public function test_production_editor_can_upload_and_complete_production(): void
    {
        Storage::fake('local');
        Notification::fake();

        [$journal, $submission] = $this->seedReadyForProduction();
        $productionEditor = User::factory()->create(['role' => 'member', 'email' => 'pe@example.com']);
        $journal->assignTeamMember($productionEditor, JournalTeamRoles::PRODUCTION_EDITOR);

        $this->actingAs($productionEditor)
            ->get(route('production.queue.show', $submission))
            ->assertOk()
            ->assertSee('Author manuscript');

        $file = UploadedFile::fake()->create('formatted.docx', 100, 'application/vnd.openxmlformats-officedocument.wordprocessingml.document');

        $this->actingAs($productionEditor)
            ->post(route('production.queue.upload', $submission), [
                'production_document' => $file,
            ])
            ->assertRedirect(route('production.queue.show', $submission));

        $submission->refresh();
        $this->assertSame(SubmissionStatus::IN_PRODUCTION, $submission->status);
        $this->assertTrue($submission->hasProductionDocument());

        $this->actingAs($productionEditor)
            ->post(route('production.queue.complete', $submission), [
                'checklist' => ['formatting_applied' => '1', 'branding_applied' => '1'],
            ])
            ->assertRedirect(route('production.queue.show', $submission));

        $submission->refresh();
        $this->assertSame(SubmissionStatus::READY_TO_PUBLISH, $submission->status);
        $this->assertNotSame($submission->document_path, $submission->currentProductionFile()?->document_path);
    }

    public function test_editor_cannot_publish_without_production_complete(): void
    {
        Storage::fake('local');

        [$journal, $submission] = $this->seedReadyForProduction();
        $editor = User::factory()->create(['role' => 'member']);
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
        $submission->update(['issue_id' => $issue->id]);

        $response = $this->actingAs($editor)
            ->post(route('journal.manage.submissions.publish', [$journal, $submission]), [
                'issue_id' => $issue->id,
            ]);

        $response->assertStatus(422);
    }

    public function test_author_cannot_access_production_upload(): void
    {
        [$journal, $submission] = $this->seedReadyForProduction();
        $author = $submission->author;

        $this->actingAs($author)
            ->get(route('production.queue.index'))
            ->assertForbidden();
    }

    /**
     * @return array{0: Journal, 1: Submission, 2: User}
     */
    private function seedAcceptedSubmission(bool $withProductionEditor = false): array
    {
        $reviewer = User::factory()->create(['role' => 'reviewer']);
        $author = User::factory()->create(['role' => 'member']);
        $journal = Journal::query()->create([
            'slug' => 'prod-journal',
            'title' => 'Production Journal',
            'is_active' => true,
        ]);

        if ($withProductionEditor) {
            $pe = User::factory()->create(['role' => 'member', 'email' => 'production@example.com']);
            $journal->assignTeamMember($pe, JournalTeamRoles::PRODUCTION_EDITOR);
        }

        $submission = Submission::query()->create([
            'id' => (string) Str::uuid(),
            'journal_id' => $journal->id,
            'author_id' => $author->id,
            'title' => 'Production test paper',
            'status' => SubmissionStatus::UNDER_REVIEW,
            'document_path' => 'journals/prod-journal/submissions/test/original.docx',
            'document_disk' => 'local',
        ]);

        ReviewerAssignment::query()->create([
            'submission_id' => $submission->id,
            'reviewer_id' => $reviewer->id,
            'status' => 'assigned',
        ]);

        return [$journal, $submission, $reviewer];
    }

    /**
     * @return array{0: Journal, 1: Submission}
     */
    private function seedReadyForProduction(): array
    {
        $author = User::factory()->create(['role' => 'member']);
        $journal = Journal::query()->create([
            'slug' => 'ready-prod-journal',
            'title' => 'Ready Prod Journal',
            'is_active' => true,
        ]);
        $submission = Submission::query()->create([
            'id' => (string) Str::uuid(),
            'journal_id' => $journal->id,
            'author_id' => $author->id,
            'title' => 'Ready for production',
            'status' => SubmissionStatus::READY_FOR_PRODUCTION,
            'document_path' => 'journals/ready-prod-journal/submissions/test/original.docx',
            'document_disk' => 'local',
            'reviewed_at' => now(),
        ]);

        return [$journal, $submission];
    }
}
