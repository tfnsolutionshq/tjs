<?php

namespace Tests\Feature\Api\V1;

use App\Models\EmailVerificationCode;
use App\Models\Journal;
use App\Models\User;
use App\Support\JournalTeamRoles;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_returns_token_and_capabilities(): void
    {
        $user = User::factory()->create([
            'email' => 'member@example.com',
            'password' => 'password',
            'role' => 'member',
        ]);

        $journal = Journal::query()->create([
            'slug' => 'demo-journal',
            'title' => 'Demo Journal',
            'is_active' => true,
        ]);
        $journal->assignTeamMember($user, JournalTeamRoles::REVIEWER);

        $response = $this->postJson('/api/v1/auth/login', [
            'email' => 'member@example.com',
            'password' => 'password',
            'device_name' => 'ios-test',
        ]);

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    'token',
                    'token_type',
                    'user' => ['id', 'name', 'email', 'role', 'email_verified'],
                    'capabilities' => [
                        'platform_admin',
                        'can_review',
                        'can_produce',
                        'managed_journals',
                        'staff_journals',
                        'production_journals',
                    ],
                ],
            ])
            ->assertJsonPath('data.token_type', 'Bearer')
            ->assertJsonPath('data.user.email', 'member@example.com')
            ->assertJsonPath('data.capabilities.can_review', true)
            ->assertJsonPath('data.capabilities.staff_journals.0.slug', 'demo-journal');
    }

    public function test_login_rejects_invalid_credentials(): void
    {
        User::factory()->create([
            'email' => 'member@example.com',
            'password' => 'password',
        ]);

        $this->postJson('/api/v1/auth/login', [
            'email' => 'member@example.com',
            'password' => 'wrong-password',
        ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['email']);
    }

    public function test_user_endpoint_requires_authentication(): void
    {
        $this->getJson('/api/v1/auth/user')
            ->assertUnauthorized();
    }

    public function test_authenticated_user_can_fetch_profile(): void
    {
        $user = User::factory()->create(['role' => 'admin']);

        Sanctum::actingAs($user);

        $this->getJson('/api/v1/auth/user')
            ->assertOk()
            ->assertJsonPath('data.user.email', $user->email)
            ->assertJsonPath('data.capabilities.platform_admin', true);
    }

    public function test_logout_revokes_current_token(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('mobile')->plainTextToken;

        $this->withToken($token)
            ->postJson('/api/v1/auth/logout')
            ->assertOk();

        $this->assertDatabaseCount('personal_access_tokens', 0);

        auth()->forgetGuards();

        $this->withToken($token)
            ->getJson('/api/v1/auth/user')
            ->assertUnauthorized();
    }

    public function test_verify_email_accepts_otp(): void
    {
        Notification::fake();

        $user = User::factory()->unverified()->create();
        $code = '123456';

        EmailVerificationCode::query()->create([
            'user_id' => $user->id,
            'code_hash' => Hash::make($code),
            'expires_at' => now()->addMinutes(15),
        ]);

        Sanctum::actingAs($user);

        $this->postJson('/api/v1/auth/verify-email', ['code' => $code])
            ->assertOk()
            ->assertJsonPath('data.user.email_verified', true);

        $this->assertTrue($user->fresh()->hasVerifiedEmail());
    }

    public function test_api_disabled_returns_service_unavailable(): void
    {
        config(['api.enabled' => false]);

        $this->postJson('/api/v1/auth/login', [
            'email' => 'x@example.com',
            'password' => 'password',
        ])->assertStatus(503);
    }
}
