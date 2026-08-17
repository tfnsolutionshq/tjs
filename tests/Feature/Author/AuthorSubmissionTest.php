<?php

namespace Tests\Feature\Author;

use App\Models\Category;
use App\Models\Issue;
use App\Models\Journal;
use App\Models\JournalAnnouncement;
use App\Models\ReviewerAssignment;
use App\Models\Submission;
use App\Models\User;
use App\Models\Volume;
use App\Services\Journal\CategoryService;
use App\Support\AnnouncementType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Tests\TestCase;

class AuthorSubmissionTest extends TestCase
{
    use RefreshDatabase;

    private function seedOpenCall(): array
    {
        $journal = Journal::query()->create([
            'slug' => 'demo-journal',
            'title' => 'Demo Journal',
            'is_active' => true,
        ]);
        app(CategoryService::class)->seedDefaults($journal);

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
        $call = JournalAnnouncement::query()->create([
            'journal_id' => $journal->id,
            'issue_id' => $issue->id,
            'type' => AnnouncementType::CALL_FOR_SUBMISSIONS,
            'title' => 'Special issue call',
            'is_published' => true,
            'opens_at' => now()->subDay(),
            'closes_at' => now()->addWeek(),
        ]);

        return compact('journal', 'issue', 'call');
    }

    public function test_create_page_empty_state_when_no_open_calls(): void
    {
        $member = User::factory()->create(['role' => 'member']);

        $this->actingAs($member)
            ->get(route('author.submissions.create'))
            ->assertOk()
            ->assertSee('No open calls right now')
            ->assertSee('Browse journals')
            ->assertSee('Prepare DOC/DOCX');
    }

    public function test_member_can_create_submission_for_open_call(): void
    {
        Storage::fake('local');

        ['journal' => $journal, 'issue' => $issue, 'call' => $call] = $this->seedOpenCall();
        $member = User::factory()->create(['role' => 'member']);
        $category = Category::query()->forJournal($journal)->where('name', 'Research Article')->firstOrFail();

        $response = $this->actingAs($member)->post(route('author.submissions.store'), [
            'announcement_id' => $call->id,
            'title' => 'Sample manuscript title',
            'abstract' => 'Sample abstract.',
            'category' => $category->name,
            'keywords' => 'testing, submissions',
            'document' => UploadedFile::fake()->create('manuscript.docx', 120, 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'),
        ]);

        $response->assertRedirect();
        $response->assertSessionHasNoErrors();

        $this->assertDatabaseHas('submissions', [
            'journal_id' => $journal->id,
            'issue_id' => $issue->id,
            'announcement_id' => $call->id,
            'author_id' => $member->id,
            'title' => 'Sample manuscript title',
            'category' => $category->name,
            'status' => 'submitted',
        ]);
    }

    public function test_submission_rejects_pdf_uploads(): void
    {
        Storage::fake('local');

        ['call' => $call] = $this->seedOpenCall();
        $member = User::factory()->create(['role' => 'member']);

        $this->actingAs($member)
            ->from(route('author.submissions.create'))
            ->post(route('author.submissions.store'), [
                'announcement_id' => $call->id,
                'title' => 'Sample manuscript title',
                'document' => UploadedFile::fake()->create('manuscript.pdf', 120, 'application/pdf'),
            ])
            ->assertRedirect(route('author.submissions.create'))
            ->assertSessionHasErrors('document');
    }

    public function test_submission_requires_open_call(): void
    {
        Storage::fake('local');

        ['call' => $call] = $this->seedOpenCall();
        $call->update(['closes_at' => now()->subHour()]);

        $member = User::factory()->create(['role' => 'member']);

        $this->actingAs($member)
            ->from(route('author.submissions.create'))
            ->post(route('author.submissions.store'), [
                'announcement_id' => $call->id,
                'title' => 'Sample manuscript title',
                'document' => UploadedFile::fake()->create('manuscript.docx', 120, 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'),
            ])
            ->assertStatus(422);
    }

    public function test_member_can_resubmit_revision_with_document(): void
    {
        Storage::fake('local');

        ['journal' => $journal] = $this->seedOpenCall();
        $member = User::factory()->create(['role' => 'member']);
        $reviewer = User::factory()->create(['role' => 'reviewer']);
        $submission = Submission::query()->create([
            'id' => (string) Str::uuid(),
            'journal_id' => $journal->id,
            'author_id' => $member->id,
            'reviewer_id' => $reviewer->id,
            'title' => 'Sample submission',
            'status' => 'revision_requested',
            'document_path' => 'journals/demo-journal/submissions/old.docx',
        ]);
        ReviewerAssignment::query()->create([
            'submission_id' => $submission->id,
            'reviewer_id' => $reviewer->id,
            'status' => 'completed',
        ]);

        $this->actingAs($member)
            ->post(route('author.submissions.resubmit', $submission), [
                'notes' => 'Major corrections are now made.',
                'document' => UploadedFile::fake()->create('revised.docx', 120, 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'),
            ])
            ->assertRedirect(route('author.submissions.show', $submission))
            ->assertSessionHasNoErrors();

        $submission->refresh();

        $this->assertSame('resubmitted', $submission->status);
        $this->assertDatabaseHas('reviewer_assignments', [
            'submission_id' => $submission->id,
            'reviewer_id' => $reviewer->id,
            'status' => 'assigned',
        ]);
    }
}
