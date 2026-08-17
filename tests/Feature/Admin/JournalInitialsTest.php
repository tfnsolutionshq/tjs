<?php

namespace Tests\Feature\Admin;

use App\Models\Journal;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class JournalInitialsTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_create_journal_with_initials(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)
            ->post(route('admin.journals.store'), [
                'title' => 'African Journal of Digital Infrastructure',
                'slug' => 'ajdi',
                'initials' => 'AJDI',
                'is_active' => '1',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('journals', [
            'slug' => 'ajdi',
            'initials' => 'AJDI',
        ]);
    }

    public function test_journal_initials_do_not_need_to_be_unique(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        Journal::query()->create([
            'slug' => 'first-journal',
            'title' => 'First Journal',
            'initials' => 'TJS',
            'is_active' => true,
        ]);

        $this->actingAs($admin)
            ->post(route('admin.journals.store'), [
                'title' => 'Second Journal',
                'slug' => 'second-journal',
                'initials' => 'TJS',
                'is_active' => '1',
            ])
            ->assertRedirect();

        $this->assertSame(2, Journal::query()->where('initials', 'TJS')->count());
    }

    public function test_display_initials_falls_back_to_title_when_not_set(): void
    {
        $journal = Journal::query()->create([
            'slug' => 'demo-journal',
            'title' => 'TFN Open Research Journal',
            'is_active' => true,
        ]);

        $this->assertSame('TOR', $journal->displayInitials());
    }
}
