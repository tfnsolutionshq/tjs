<?php

namespace Tests\Feature;

use App\Models\Journal;
use App\Models\MembershipPlan;
use App\Models\User;
use App\Support\JournalActivation;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MembershipListingTest extends TestCase
{
    use RefreshDatabase;

    private function makeJournal(array $overrides = []): Journal
    {
        return Journal::query()->create(array_merge([
            'slug' => 'demo-journal',
            'title' => 'Demo Journal',
            'is_active' => true,
            'is_featured' => false,
            'activation_status' => JournalActivation::STATUS_ACTIVE,
            'activation_expires_at' => now()->addYear(),
        ], $overrides));
    }

    public function test_memberships_page_shows_featured_journal_plans_first(): void
    {
        $featuredJournal = $this->makeJournal([
            'slug' => 'featured-journal',
            'title' => 'Featured Journal',
            'is_featured' => true,
        ]);
        $regularJournal = $this->makeJournal([
            'slug' => 'regular-journal',
            'title' => 'Regular Journal',
            'is_featured' => false,
        ]);

        MembershipPlan::query()->create([
            'journal_id' => $featuredJournal->id,
            'name' => 'Featured plan',
            'scope' => 'journal',
            'price_amount' => 10000,
            'currency' => 'NGN',
            'duration_days' => 365,
            'is_active' => true,
        ]);

        MembershipPlan::query()->create([
            'journal_id' => $regularJournal->id,
            'name' => 'Regular plan',
            'scope' => 'journal',
            'price_amount' => 12000,
            'currency' => 'NGN',
            'duration_days' => 365,
            'is_active' => true,
        ]);

        $user = User::factory()->create(['role' => 'member']);

        $this->actingAs($user)
            ->get(route('memberships.index'))
            ->assertOk()
            ->assertSee('Featured plan', false)
            ->assertSee('Browse all journal memberships', false)
            ->assertDontSee('Regular plan', false);

        $this->actingAs($user)
            ->get(route('memberships.index', ['scope' => 'all']))
            ->assertOk()
            ->assertSee('Featured plan', false)
            ->assertSee('Regular plan', false);
    }
}
