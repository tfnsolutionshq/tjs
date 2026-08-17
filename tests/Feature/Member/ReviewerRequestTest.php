<?php

namespace Tests\Feature\Member;

use App\Models\Journal;
use App\Models\JournalReviewerRequest;
use App\Models\Membership;
use App\Models\MembershipPlan;
use App\Models\User;
use App\Support\JournalTeamRoles;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReviewerRequestTest extends TestCase
{
    use RefreshDatabase;

    private function journalMember(User $user, Journal $journal): void
    {
        $plan = MembershipPlan::query()->create([
            'name' => 'Journal plan',
            'scope' => 'journal',
            'journal_id' => $journal->id,
            'price_amount' => 10000,
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
    }

    public function test_journal_member_can_submit_reviewer_request(): void
    {
        $user = User::factory()->create(['role' => 'member']);
        $journal = Journal::query()->create([
            'slug' => 'demo-journal',
            'title' => 'Demo Journal',
            'is_active' => true,
        ]);
        $this->journalMember($user, $journal);

        $this->actingAs($user)
            ->post(route('reviewer-requests.store', $journal), [
                'message' => 'I would like to review education papers.',
            ])
            ->assertRedirect()
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('journal_reviewer_requests', [
            'journal_id' => $journal->id,
            'user_id' => $user->id,
            'status' => JournalReviewerRequest::STATUS_PENDING,
        ]);
    }

    public function test_non_member_cannot_submit_reviewer_request(): void
    {
        $user = User::factory()->create(['role' => 'member']);
        $journal = Journal::query()->create([
            'slug' => 'demo-journal',
            'title' => 'Demo Journal',
            'is_active' => true,
        ]);

        $this->actingAs($user)
            ->post(route('reviewer-requests.store', $journal))
            ->assertSessionHasErrors('reviewer_request');
    }

    public function test_journal_admin_can_approve_reviewer_request(): void
    {
        $member = User::factory()->create(['role' => 'member']);
        $editor = User::factory()->create(['role' => 'member']);
        $journal = Journal::query()->create([
            'slug' => 'demo-journal',
            'title' => 'Demo Journal',
            'is_active' => true,
        ]);
        $this->journalMember($member, $journal);
        $journal->assignTeamMember($editor, JournalTeamRoles::EDITOR);

        $request = JournalReviewerRequest::query()->create([
            'journal_id' => $journal->id,
            'user_id' => $member->id,
            'status' => JournalReviewerRequest::STATUS_PENDING,
            'message' => 'Ready to review.',
        ]);

        $this->actingAs($editor)
            ->post(route('journal.manage.reviewer-requests.approve', [$journal, $request]))
            ->assertRedirect()
            ->assertSessionHasNoErrors();

        $request->refresh();
        $member->refresh();

        $this->assertSame(JournalReviewerRequest::STATUS_APPROVED, $request->status);
        $this->assertSame(JournalTeamRoles::REVIEWER, $member->journalTeamRole($journal));
        $this->assertSame('reviewer', $member->role);
    }

    public function test_member_can_withdraw_pending_request(): void
    {
        $user = User::factory()->create(['role' => 'member']);
        $journal = Journal::query()->create([
            'slug' => 'demo-journal',
            'title' => 'Demo Journal',
            'is_active' => true,
        ]);
        $this->journalMember($user, $journal);

        JournalReviewerRequest::query()->create([
            'journal_id' => $journal->id,
            'user_id' => $user->id,
            'status' => JournalReviewerRequest::STATUS_PENDING,
        ]);

        $this->actingAs($user)
            ->delete(route('reviewer-requests.destroy', $journal))
            ->assertRedirect()
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('journal_reviewer_requests', [
            'journal_id' => $journal->id,
            'user_id' => $user->id,
            'status' => JournalReviewerRequest::STATUS_WITHDRAWN,
        ]);
    }
}
