<?php

namespace Tests\Feature\Api\V1;

use App\Models\Journal;
use App\Models\Membership;
use App\Models\MembershipPlan;
use App\Models\User;
use App\Support\JournalActivation;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class MembershipTest extends TestCase
{
    use RefreshDatabase;

    public function test_memberships_index_lists_active_and_available_plans(): void
    {
        $user = User::factory()->create(['email_verified_at' => now()]);

        $featuredJournal = Journal::query()->create([
            'slug' => 'featured-journal',
            'title' => 'Featured Journal',
            'is_active' => true,
            'is_featured' => true,
            'activation_status' => JournalActivation::STATUS_ACTIVE,
        ]);
        $regularJournal = Journal::query()->create([
            'slug' => 'regular-journal',
            'title' => 'Regular Journal',
            'is_active' => true,
            'is_featured' => false,
            'activation_status' => JournalActivation::STATUS_ACTIVE,
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

        $regularPlan = MembershipPlan::query()->create([
            'journal_id' => $regularJournal->id,
            'name' => 'Regular plan',
            'scope' => 'journal',
            'price_amount' => 12000,
            'currency' => 'NGN',
            'duration_days' => 365,
            'is_active' => true,
        ]);

        Membership::query()->create([
            'user_id' => $user->id,
            'membership_plan_id' => $regularPlan->id,
            'journal_id' => $regularJournal->id,
            'scope' => 'journal',
            'status' => 'active',
            'starts_at' => now()->subDay(),
            'ends_at' => now()->addYear(),
        ]);

        Sanctum::actingAs($user);

        $response = $this->getJson('/api/v1/me/memberships')
            ->assertOk()
            ->assertJsonPath('data.active_memberships.0.plan.name', 'Regular plan')
            ->assertJsonPath('data.journal_plans.0.name', 'Featured plan');

        $journalPlanNames = collect($response->json('data.journal_plans'))->pluck('name')->all();
        $this->assertSame(['Featured plan'], $journalPlanNames);
    }
}
