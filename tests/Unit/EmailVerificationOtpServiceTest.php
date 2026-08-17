<?php

namespace Tests\Unit;

use App\Models\EmailVerificationCode;
use App\Models\User;
use App\Notifications\EmailVerificationOtpNotification;
use App\Services\Auth\EmailVerificationOtpService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class EmailVerificationOtpServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_issue_creates_hashed_code_and_sends_notification(): void
    {
        Notification::fake();

        $user = User::factory()->unverified()->create();

        app(EmailVerificationOtpService::class)->issue($user);

        $this->assertSame(1, EmailVerificationCode::query()->where('user_id', $user->id)->count());
        Notification::assertSentTo($user, EmailVerificationOtpNotification::class);
    }

    public function test_verify_marks_email_verified_and_deletes_code(): void
    {
        $user = User::factory()->unverified()->create();
        $plain = '123456';

        EmailVerificationCode::query()->create([
            'user_id' => $user->id,
            'code_hash' => Hash::make($plain),
            'expires_at' => now()->addMinutes(10),
        ]);

        app(EmailVerificationOtpService::class)->verify($user, $plain);

        $this->assertTrue($user->fresh()->hasVerifiedEmail());
        $this->assertSame(0, EmailVerificationCode::query()->where('user_id', $user->id)->count());
    }

    public function test_verify_rejects_incorrect_code(): void
    {
        $user = User::factory()->unverified()->create();

        EmailVerificationCode::query()->create([
            'user_id' => $user->id,
            'code_hash' => Hash::make('123456'),
            'expires_at' => now()->addMinutes(10),
        ]);

        $this->expectException(ValidationException::class);

        app(EmailVerificationOtpService::class)->verify($user, '654321');
    }

    public function test_active_expiry_returns_future_timestamp(): void
    {
        $user = User::factory()->unverified()->create();
        $expiresAt = now()->addMinutes(10);

        EmailVerificationCode::query()->create([
            'user_id' => $user->id,
            'code_hash' => Hash::make('123456'),
            'expires_at' => $expiresAt,
        ]);

        $active = app(EmailVerificationOtpService::class)->activeExpiry($user);

        $this->assertNotNull($active);
        $this->assertSame($expiresAt->timestamp, $active->timestamp);
    }

    public function test_resend_is_blocked_during_cooldown(): void
    {
        Notification::fake();

        $user = User::factory()->unverified()->create();
        app(EmailVerificationOtpService::class)->issue($user);

        $this->expectException(ValidationException::class);

        app(EmailVerificationOtpService::class)->resend($user);
    }

    public function test_resend_available_at_reflects_cooldown(): void
    {
        $user = User::factory()->unverified()->create();
        $service = app(EmailVerificationOtpService::class);

        $this->assertNull($service->resendAvailableAt($user));

        $service->issue($user);

        $availableAt = $service->resendAvailableAt($user);

        $this->assertNotNull($availableAt);
        $this->assertTrue($availableAt->isFuture());
    }
}
