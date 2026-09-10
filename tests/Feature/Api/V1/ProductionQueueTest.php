<?php

namespace Tests\Feature\Api\V1;

use App\Models\Journal;
use App\Models\Submission;
use App\Models\User;
use App\Support\JournalTeamRoles;
use App\Support\SubmissionStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ProductionQueueTest extends TestCase
{
    use RefreshDatabase;

    public function test_production_editor_can_list_awaiting_queue(): void
    {
        [$journal, $submission, $editor] = $this->seedReadyForProduction();

        Sanctum::actingAs($editor);

        $this->getJson('/api/v1/me/production/queue')
            ->assertOk()
            ->assertJsonPath('meta.stats.awaiting', 1)
            ->assertJsonPath('data.0.id', $submission->id);
    }

    public function test_production_editor_can_view_detail(): void
    {
        [$journal, $submission, $editor] = $this->seedReadyForProduction();

        Sanctum::actingAs($editor);

        $this->getJson('/api/v1/me/production/queue/'.$submission->id)
            ->assertOk()
            ->assertJsonPath('data.id', $submission->id)
            ->assertJsonPath('data.can_upload', true)
            ->assertJsonStructure(['data' => ['checklist', 'checklist_schema', 'author']]);
    }

    public function test_production_editor_can_upload_and_complete(): void
    {
        Storage::fake('local');

        [$journal, $submission, $editor] = $this->seedReadyForProduction();

        Sanctum::actingAs($editor);

        $file = UploadedFile::fake()->create(
            'formatted.docx',
            100,
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document'
        );

        $this->post('/api/v1/me/production/queue/'.$submission->id.'/upload', [
            'production_document' => $file,
        ], ['Accept' => 'application/json'])
            ->assertOk()
            ->assertJsonPath('data.submission.status', SubmissionStatus::IN_PRODUCTION);

        $this->postJson('/api/v1/me/production/queue/'.$submission->id.'/complete', [
            'checklist' => ['formatting_applied' => true, 'branding_applied' => true],
        ])
            ->assertOk()
            ->assertJsonPath('data.submission.status', SubmissionStatus::READY_TO_PUBLISH);

        $submission->refresh();
        $this->assertTrue($submission->hasProductionDocument());
    }

    public function test_author_cannot_access_production_queue_api(): void
    {
        [, $submission, ] = $this->seedReadyForProduction();
        $author = $submission->author;
        $author->update(['email_verified_at' => now()]);

        Sanctum::actingAs($author);

        $this->getJson('/api/v1/me/production/queue')->assertForbidden();
    }

    /**
     * @return array{0: Journal, 1: Submission, 2: User}
     */
    private function seedReadyForProduction(): array
    {
        $author = User::factory()->create(['role' => 'member']);
        $editor = User::factory()->create(['role' => 'member', 'email_verified_at' => now()]);
        $journal = Journal::query()->create([
            'slug' => 'prod-api-journal',
            'title' => 'Production API Journal',
            'is_active' => true,
        ]);
        $journal->assignTeamMember($editor, JournalTeamRoles::PRODUCTION_EDITOR);

        $submission = Submission::query()->create([
            'id' => (string) Str::uuid(),
            'journal_id' => $journal->id,
            'author_id' => $author->id,
            'title' => 'Production API paper',
            'status' => SubmissionStatus::READY_FOR_PRODUCTION,
            'document_path' => 'journals/prod-api-journal/submissions/test/original.docx',
            'document_disk' => 'local',
            'reviewed_at' => now(),
        ]);

        return [$journal, $submission, $editor];
    }
}
