<?php

namespace Tests\Feature\Auth;

use App\Models\Journal;
use App\Models\User;
use App\Notifications\EmailVerificationOtpNotification;
use App\Support\JournalActivation;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    private function makeJournal(array $overrides = []): Journal
    {
        return Journal::query()->create(array_merge([
            'slug' => 'demo-journal',
            'title' => 'Demo Journal',
            'is_active' => true,
            'is_featured' => true,
            'activation_status' => JournalActivation::STATUS_ACTIVE,
            'activation_expires_at' => now()->addYear(),
        ], $overrides));
    }

    public function test_registration_screen_can_be_rendered(): void
    {
        $this->get('/register')
            ->assertOk()
            ->assertSee('Create your account', false)
            ->assertSee(route('register.journals'), false);
    }

    public function test_registration_journal_picker_lists_featured_journals(): void
    {
        $this->makeJournal();

        $this->get(route('register.journals'))
            ->assertOk()
            ->assertSee('Search journals', false)
            ->assertSee('Demo Journal', false)
            ->assertSee('demo-journal', false);
    }

    public function test_new_users_can_register_without_a_journal(): void
    {
        Notification::fake();

        $response = $this->post('/register', [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $this->assertAuthenticated();
        $response->assertRedirect(route('verification.notice', absolute: false));

        $user = User::query()->where('email', 'test@example.com')->first();
        $this->assertNotNull($user);
        $this->assertNull($user->email_verified_at);

        Notification::assertSentTo($user, EmailVerificationOtpNotification::class);
    }

    public function test_new_users_can_register_for_a_journal(): void
    {
        Notification::fake();
        $journal = $this->makeJournal();

        $response = $this->post(route('journals.register.store', $journal), [
            'name' => 'Journal User',
            'email' => 'journal-user@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $this->assertAuthenticated();
        $response->assertRedirect(route('verification.notice', absolute: false));

        Notification::assertSentTo(
            User::query()->where('email', 'journal-user@example.com')->first(),
            EmailVerificationOtpNotification::class
        );
    }

    public function test_unverified_users_cannot_access_dashboard(): void
    {
        $user = User::factory()->unverified()->create();

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertRedirect(route('verification.notice', absolute: false));
    }
}
