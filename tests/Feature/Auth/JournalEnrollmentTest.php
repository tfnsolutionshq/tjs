<?php

namespace Tests\Feature\Auth;

use App\Models\Journal;
use App\Models\Membership;
use App\Models\MembershipPlan;
use App\Models\User;
use App\Notifications\EmailVerificationOtpNotification;
use App\Support\JournalActivation;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class JournalEnrollmentTest extends TestCase
{
    use RefreshDatabase;

    private function makeListedJournal(array $overrides = []): Journal
    {
        return Journal::query()->create(array_merge([
            'slug' => 'free-journal',
            'title' => 'Free Journal',
            'is_active' => true,
            'is_featured' => false,
            'activation_status' => JournalActivation::STATUS_ACTIVE,
            'activation_expires_at' => now()->addYear(),
        ], $overrides));
    }

    public function test_journal_enrollment_grants_free_membership_when_no_active_paid_plan(): void
    {
        Notification::fake();

        $journal = $this->makeListedJournal(['slug' => 'unizik-style']);
        MembershipPlan::query()->create([
            'journal_id' => $journal->id,
            'name' => 'Annual Membership',
            'scope' => 'journal',
            'price_amount' => 15000,
            'currency' => 'NGN',
            'duration_days' => 365,
            'is_active' => false,
        ]);

        $this->post(route('journals.register.store', $journal), [
            'name' => 'Free Reader',
            'email' => 'free-reader@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ])->assertRedirect(route('verification.notice', absolute: false));

        $user = User::query()->where('email', 'free-reader@example.com')->firstOrFail();

        $code = null;
        Notification::assertSentTo($user, EmailVerificationOtpNotification::class, function (EmailVerificationOtpNotification $notification) use (&$code) {
            $code = $notification->code;

            return true;
        });

        $this->actingAs($user)
            ->post('/verify-email/otp', ['code' => $code])
            ->assertRedirect(route('journals.show', $journal))
            ->assertSessionHas('status');

        $this->assertTrue(
            Membership::query()
                ->where('user_id', $user->id)
                ->where('journal_id', $journal->id)
                ->where('scope', 'journal')
                ->where('status', 'active')
                ->exists()
        );
    }

    public function test_journal_login_grants_free_membership_for_existing_user(): void
    {
        $journal = $this->makeListedJournal(['slug' => 'login-free']);
        $user = User::factory()->create(['email' => 'member@example.com', 'password' => bcrypt('password')]);

        $this->post(route('journals.login.store', $journal), [
            'email' => 'member@example.com',
            'password' => 'password',
        ])->assertRedirect()
            ->assertSessionHas('status');

        $this->assertTrue(
            Membership::query()
                ->where('user_id', $user->id)
                ->where('journal_id', $journal->id)
                ->where('scope', 'journal')
                ->where('status', 'active')
                ->exists()
        );
    }

    public function test_authenticated_user_can_join_journal_without_fee(): void
    {
        $journal = $this->makeListedJournal(['slug' => 'join-free']);
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post(route('journals.join', $journal))
            ->assertRedirect()
            ->assertSessionHas('status');

        $this->assertTrue($user->isJournalMember($journal));
    }
}
