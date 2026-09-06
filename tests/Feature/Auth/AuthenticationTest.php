<?php

namespace Tests\Feature\Auth;

use App\Models\Journal;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    private function makeJournal(array $overrides = []): Journal
    {
        return Journal::query()->create(array_merge([
            'slug' => 'demo-journal',
            'title' => 'Demo Journal',
            'is_active' => true,
        ], $overrides));
    }

    public function test_login_screen_can_be_rendered(): void
    {
        $this->get('/login')
            ->assertOk()
            ->assertSee('Welcome back', false)
            ->assertSee(route('login.journals'), false);
    }

    public function test_login_journal_picker_lists_active_journals(): void
    {
        $active = $this->makeJournal();
        $this->makeJournal([
            'slug' => 'closed-journal',
            'title' => 'Closed Journal',
            'is_active' => false,
        ]);

        $this->get(route('login.journals'))
            ->assertOk()
            ->assertSee('Search journals', false)
            ->assertSee('Demo Journal', false)
            ->assertSee('demo-journal', false)
            ->assertDontSee('Closed Journal', false);
    }

    public function test_users_can_authenticate_using_the_login_screen(): void
    {
        $user = User::factory()->create();

        $response = $this->post('/login', [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $this->assertAuthenticated();
        $response->assertRedirect(route('dashboard', absolute: false));
    }

    public function test_admins_are_redirected_to_admin_dashboard_after_login(): void
    {
        $user = User::factory()->create(['role' => 'admin']);

        $response = $this->post('/login', [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $this->assertAuthenticated();
        $response->assertRedirect(route('admin.dashboard', absolute: false));
    }

    public function test_journal_login_redirects_to_journal_home(): void
    {
        $journal = $this->makeJournal();
        $user = User::factory()->create();

        $response = $this->post(route('journals.login.store', $journal), [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $this->assertAuthenticated();
        $response->assertRedirect(route('journals.show', $journal, absolute: false));
    }

    public function test_inactive_journal_login_returns_not_found(): void
    {
        $journal = $this->makeJournal(['is_active' => false, 'slug' => 'inactive-j']);

        $this->get(route('journals.login', $journal))->assertNotFound();
    }

    public function test_users_can_not_authenticate_with_invalid_password(): void
    {
        $user = User::factory()->create();

        $this->post('/login', [
            'email' => $user->email,
            'password' => 'wrong-password',
        ]);

        $this->assertGuest();
    }

    public function test_users_can_logout(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post('/logout');

        $this->assertGuest();
        $response->assertRedirect('/');
    }
}
