<?php

namespace Tests\Unit;

use App\Models\Journal;
use App\Models\Membership;
use App\Models\MembershipPlan;
use App\Models\User;
use App\Services\Membership\MembershipCoverageService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MembershipCoverageServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_journal_membership_covers_same_journal_plan(): void
    {
        $user = User::factory()->create();
        $journal = Journal::query()->create([
            'slug' => 'demo-journal',
            'title' => 'Demo Journal',
            'is_active' => true,
        ]);

        $plan = MembershipPlan::query()->create([
            'name' => 'Annual',
            'scope' => 'journal',
            'journal_id' => $journal->id,
            'price_amount' => 15000,
            'currency' => 'NGN',
            'duration_days' => 365,
            'is_active' => true,
        ]);

        Membership::query()->create([
            'user_id' => $user->id,
            'membership_plan_id' => $plan->id,
            'journal_id' => $journal->id,
            'scope' => 'journal',
            'status' => 'active',
            'starts_at' => now()->subDay(),
            'ends_at' => now()->addYear(),
        ]);

        $otherPlan = MembershipPlan::query()->create([
            'name' => 'Annual duplicate',
            'scope' => 'journal',
            'journal_id' => $journal->id,
            'price_amount' => 15000,
            'currency' => 'NGN',
            'duration_days' => 365,
            'is_active' => true,
        ]);

        $service = app(MembershipCoverageService::class);

        $this->assertTrue($service->planIsCovered($user, $plan));
        $this->assertTrue($service->planIsCovered($user, $otherPlan));
    }

    public function test_platform_membership_covers_journal_plan(): void
    {
        $user = User::factory()->create();
        $journal = Journal::query()->create([
            'slug' => 'demo-journal',
            'title' => 'Demo Journal',
            'is_active' => true,
        ]);

        $platformPlan = MembershipPlan::query()->create([
            'name' => 'Platform Annual',
            'scope' => 'platform',
            'price_amount' => 15000,
            'currency' => 'NGN',
            'duration_days' => 365,
            'is_active' => true,
        ]);

        Membership::query()->create([
            'user_id' => $user->id,
            'membership_plan_id' => $platformPlan->id,
            'journal_id' => null,
            'scope' => 'platform',
            'status' => 'active',
            'starts_at' => now()->subDay(),
            'ends_at' => now()->addYear(),
        ]);

        $journalPlan = MembershipPlan::query()->create([
            'name' => 'Journal Annual',
            'scope' => 'journal',
            'journal_id' => $journal->id,
            'price_amount' => 10000,
            'currency' => 'NGN',
            'duration_days' => 365,
            'is_active' => true,
        ]);

        $this->assertTrue(app(MembershipCoverageService::class)->planIsCovered($user, $journalPlan));
    }
}
