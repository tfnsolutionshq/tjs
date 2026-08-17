<?php

namespace Tests\Feature\Public;

use App\Models\Issue;
use App\Models\Journal;
use App\Models\JournalAnnouncement;
use App\Models\User;
use App\Models\Volume;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class JournalListingTest extends TestCase
{
    use RefreshDatabase;

    public function test_journals_index_paginates_and_supports_layout_toggle(): void
    {
        foreach (range(1, 13) as $i) {
            Journal::create([
                'slug' => 'journal-'.$i,
                'title' => 'Journal '.$i,
                'publisher' => 'TFN',
                'is_active' => true,
            ]);
        }

        $this->get(route('journals.index'))
            ->assertOk()
            ->assertSee('Showing 1')
            ->assertSee('of 13')
            ->assertSee('jp-layout-toggle', false)
            ->assertSee('Journal 1')
            ->assertSee('Journal 12');

        $this->get(route('journals.index', ['page' => 2]))
            ->assertOk()
            ->assertSee('Journal 9');

        $this->get(route('journals.index', ['view' => 'list']))
            ->assertOk()
            ->assertSee('is-list', false);
    }

    public function test_author_submission_create_uses_searchable_journal_picker(): void
    {
        ['call' => $call] = $this->seedOpenCallHelper();

        $this->actingAs($this->createUser())
            ->get(route('author.submissions.create'))
            ->assertOk()
            ->assertSee('tjs-call-picker', false)
            ->assertSee('Special issue call');
    }

    private function seedOpenCallHelper(): array
    {
        $journal = Journal::query()->create([
            'slug' => 'demo',
            'title' => 'Demo Journal',
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
            'status' => 'published',
        ]);
        $call = JournalAnnouncement::query()->create([
            'journal_id' => $journal->id,
            'issue_id' => $issue->id,
            'type' => \App\Support\AnnouncementType::CALL_FOR_SUBMISSIONS,
            'title' => 'Special issue call',
            'is_published' => true,
            'opens_at' => now()->subDay(),
            'closes_at' => now()->addWeek(),
        ]);

        return compact('journal', 'call');
    }

    private function createUser(): \App\Models\User
    {
        return \App\Models\User::factory()->create([
            'email_verified_at' => now(),
        ]);
    }
}
