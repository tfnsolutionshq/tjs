<?php

namespace Tests\Feature\Auth;

use App\Models\Journal;
use App\Models\MembershipPlan;
use App\Models\User;
use App\Notifications\EmailVerificationOtpNotification;
use App\Support\JournalActivation;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class JournalPickerTest extends TestCase
{
    use RefreshDatabase;

    private function makeListedJournal(array $overrides = []): Journal
    {
        return Journal::query()->create(array_merge([
            'slug' => 'featured-journal',
            'title' => 'Featured Journal',
            'is_active' => true,
            'is_featured' => true,
            'activation_status' => JournalActivation::STATUS_ACTIVE,
            'activation_expires_at' => now()->addYear(),
        ], $overrides));
    }

    public function test_picker_endpoint_returns_featured_journals_only_in_other_mode(): void
    {
        $this->makeListedJournal(['slug' => 'featured-one', 'title' => 'Featured One', 'is_featured' => true]);
        $this->makeListedJournal([
            'slug' => 'regular-one',
            'title' => 'Regular One',
            'is_featured' => false,
        ]);

        $this->getJson(route('journals.picker', [
            'action' => 'login',
            'mode' => 'other',
            'page' => 1,
        ]))
            ->assertOk()
            ->assertJsonPath('meta.total', 1)
            ->assertJsonPath('data.0.slug', 'regular-one')
            ->assertJsonMissing(['slug' => 'featured-one']);
    }

    public function test_picker_endpoint_searches_all_journals(): void
    {
        $this->makeListedJournal(['slug' => 'alpha-journal', 'title' => 'Alpha Journal']);
        $this->makeListedJournal([
            'slug' => 'beta-journal',
            'title' => 'Beta Journal',
            'is_featured' => false,
        ]);

        $this->getJson(route('journals.picker', [
            'action' => 'register',
            'mode' => 'all',
            'q' => 'beta',
        ]))
            ->assertOk()
            ->assertJsonPath('meta.total', 1)
            ->assertJsonPath('data.0.slug', 'beta-journal');
    }

    public function test_login_choose_journal_page_shows_featured_only_by_default(): void
    {
        $this->makeListedJournal(['slug' => 'featured-one', 'title' => 'Featured One', 'is_featured' => true]);
        $this->makeListedJournal([
            'slug' => 'regular-one',
            'title' => 'Regular One',
            'is_featured' => false,
        ]);

        $this->get(route('login.journals'))
            ->assertOk()
            ->assertSee('Featured One', false)
            ->assertSee('Browse all journals', false)
            ->assertDontSee('Regular One', false);
    }

    public function test_journal_registration_redirects_to_membership_checkout_after_verification(): void
    {
        Notification::fake();

        $journal = $this->makeListedJournal(['slug' => 'paywall-journal', 'title' => 'Paywall Journal']);
        $plan = MembershipPlan::query()->create([
            'journal_id' => $journal->id,
            'name' => 'Reader membership',
            'scope' => 'journal',
            'price_amount' => 15000,
            'currency' => 'NGN',
            'duration_days' => 365,
            'is_active' => true,
        ]);

        $this->post(route('journals.register.store', $journal), [
            'name' => 'New Reader',
            'email' => 'reader@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ])->assertRedirect(route('verification.notice', absolute: false));

        $user = User::query()->where('email', 'reader@example.com')->firstOrFail();

        $code = null;
        Notification::assertSentTo($user, EmailVerificationOtpNotification::class, function (EmailVerificationOtpNotification $notification) use (&$code) {
            $code = $notification->code;

            return true;
        });

        $this->actingAs($user)
            ->post('/verify-email/otp', ['code' => $code])
            ->assertRedirect(route('memberships.checkout', $plan, absolute: false))
            ->assertSessionHas('status');
    }
}
