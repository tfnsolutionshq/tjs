<?php

namespace Tests\Feature\Admin;

use App\Models\Journal;
use App\Models\User;
use App\Support\JournalActivation;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FeaturedJournalRequestTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_sidebar_shows_featured_request_badge(): void
    {
        Journal::query()->create([
            'slug' => 'sidebar-journal',
            'title' => 'Sidebar Journal',
            'is_active' => true,
            'is_featured' => false,
            'featured_requested_at' => now(),
            'activation_status' => JournalActivation::STATUS_ACTIVE,
        ]);

        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSee('admin-nav__badge--featured', false)
            ->assertSee('>1<', false);
    }

    public function test_journal_manager_request_appears_for_platform_admin(): void
    {
        $journal = Journal::query()->create([
            'slug' => 'requesting-journal',
            'title' => 'Requesting Journal',
            'is_active' => true,
            'is_featured' => false,
            'activation_status' => JournalActivation::STATUS_ACTIVE,
        ]);

        $manager = User::factory()->create();
        $journal->assignTeamMember($manager, \App\Support\JournalTeamRoles::ADMIN);

        $this->actingAs($manager)
            ->post(route('journal.manage.settings.featured-request', $journal))
            ->assertRedirect();

        $journal->refresh();
        $this->assertNotNull($journal->featured_requested_at);

        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)
            ->get(route('admin.journals.index', ['status' => 'featured_requests']))
            ->assertOk()
            ->assertSee('Requesting Journal');
    }

    public function test_platform_admin_can_filter_and_approve_featured_request(): void
    {
        $journal = Journal::query()->create([
            'slug' => 'requesting-journal',
            'title' => 'Requesting Journal',
            'is_active' => true,
            'is_featured' => false,
            'featured_requested_at' => now(),
            'activation_status' => JournalActivation::STATUS_ACTIVE,
        ]);

        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)
            ->get(route('admin.journals.index', ['status' => 'featured_requests']))
            ->assertOk()
            ->assertSee('Requesting Journal')
            ->assertSee('Featured request');

        $this->actingAs($admin)
            ->post(route('admin.journals.featured.approve', $journal))
            ->assertRedirect();

        $journal->refresh();
        $this->assertTrue($journal->is_featured);
        $this->assertNull($journal->featured_requested_at);
    }

    public function test_platform_admin_can_dismiss_featured_request(): void
    {
        $journal = Journal::query()->create([
            'slug' => 'dismiss-journal',
            'title' => 'Dismiss Journal',
            'is_active' => true,
            'is_featured' => false,
            'featured_requested_at' => now(),
            'activation_status' => JournalActivation::STATUS_ACTIVE,
        ]);

        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)
            ->post(route('admin.journals.featured.dismiss', $journal))
            ->assertRedirect();

        $journal->refresh();
        $this->assertFalse($journal->is_featured);
        $this->assertNull($journal->featured_requested_at);
    }
}
