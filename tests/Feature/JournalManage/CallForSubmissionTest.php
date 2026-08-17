<?php

namespace Tests\Feature\JournalManage;

use App\Models\Issue;
use App\Models\Journal;
use App\Models\JournalAnnouncement;
use App\Models\User;
use App\Models\Volume;
use App\Support\AnnouncementType;
use App\Support\JournalTeamRoles;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CallForSubmissionTest extends TestCase
{
    use RefreshDatabase;

    public function test_journal_manager_can_create_call_for_submissions(): void
    {
        $journal = Journal::query()->create([
            'slug' => 'demo-journal',
            'title' => 'Demo Journal',
            'is_active' => true,
        ]);
        $manager = User::factory()->create(['role' => 'member']);
        $journal->assignTeamMember($manager, JournalTeamRoles::ADMIN);

        $volume = Volume::query()->create([
            'journal_id' => $journal->id,
            'volume_number' => 1,
            'year' => 2026,
            'status' => 'published',
        ]);
        $issue = Issue::query()->create([
            'volume_id' => $volume->id,
            'issue_number' => 2,
            'status' => 'draft',
        ]);

        $this->actingAs($manager)
            ->post(route('journal.manage.announcements.store', $journal), [
                'type' => AnnouncementType::CALL_FOR_SUBMISSIONS,
                'title' => 'Winter 2026 call',
                'summary' => 'Submit research articles.',
                'body' => 'We invite submissions for the winter issue.',
                'issue_id' => $issue->id,
                'opens_at' => now()->format('Y-m-d\TH:i'),
                'closes_at' => now()->addMonth()->format('Y-m-d\TH:i'),
                'is_published' => '1',
            ])
            ->assertRedirect(route('journal.manage.announcements.index', $journal))
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('journal_announcements', [
            'journal_id' => $journal->id,
            'issue_id' => $issue->id,
            'type' => AnnouncementType::CALL_FOR_SUBMISSIONS,
            'title' => 'Winter 2026 call',
            'is_published' => true,
        ]);
    }

    public function test_manager_can_close_call_for_submissions(): void
    {
        $journal = Journal::query()->create([
            'slug' => 'demo-journal',
            'title' => 'Demo Journal',
            'is_active' => true,
        ]);
        $manager = User::factory()->create(['role' => 'member']);
        $journal->assignTeamMember($manager, JournalTeamRoles::EDITOR);

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
            'title' => 'Open call',
            'is_published' => true,
            'opens_at' => now()->subDay(),
            'closes_at' => now()->addWeek(),
            'created_by' => $manager->id,
        ]);

        $this->actingAs($manager)
            ->post(route('journal.manage.announcements.close', [$journal, $call]))
            ->assertRedirect(route('journal.manage.announcements.index', $journal));

        $call->refresh();

        $this->assertFalse($call->acceptsSubmissions());
    }

    public function test_public_announcements_page_lists_published_items(): void
    {
        $journal = Journal::query()->create([
            'slug' => 'demo-journal',
            'title' => 'Demo Journal',
            'is_active' => true,
        ]);

        JournalAnnouncement::query()->create([
            'journal_id' => $journal->id,
            'type' => AnnouncementType::NEWS,
            'title' => 'Editorial update',
            'is_published' => true,
        ]);

        $this->get(route('journals.announcements', $journal))
            ->assertOk()
            ->assertSee('Editorial update');
    }
}
