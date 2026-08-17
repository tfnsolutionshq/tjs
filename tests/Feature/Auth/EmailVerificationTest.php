<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use App\Notifications\EmailVerificationOtpNotification;
use Illuminate\Auth\Events\Verified;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class EmailVerificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_email_verification_screen_can_be_rendered(): void
    {
        $user = User::factory()->unverified()->create();

        $response = $this->actingAs($user)->get('/verify-email');

        $response->assertStatus(200);
    }

    public function test_email_can_be_verified_with_otp(): void
    {
        $user = User::factory()->unverified()->create();

        Event::fake();
        Notification::fake();

        $user->sendEmailVerificationNotification();

        $code = null;
        Notification::assertSentTo($user, EmailVerificationOtpNotification::class, function (EmailVerificationOtpNotification $notification) use (&$code) {
            $code = $notification->code;

            return true;
        });

        $response = $this->actingAs($user)->post('/verify-email/otp', [
            'code' => $code,
        ]);

        Event::assertDispatched(Verified::class);
        $this->assertTrue($user->fresh()->hasVerifiedEmail());
        $response->assertRedirect(route('dashboard', absolute: false).'?verified=1');
    }

    public function test_email_is_not_verified_with_invalid_otp(): void
    {
        Notification::fake();

        $user = User::factory()->unverified()->create();
        $user->sendEmailVerificationNotification();

        $this->actingAs($user)->post('/verify-email/otp', [
            'code' => '000000',
        ])->assertSessionHasErrors('code');

        $this->assertFalse($user->fresh()->hasVerifiedEmail());
    }

    public function test_verification_code_can_be_resent(): void
    {
        Notification::fake();

        $user = User::factory()->unverified()->create();

        $this->actingAs($user)->post('/email/verification-notification')
            ->assertRedirect()
            ->assertSessionHas('status', 'verification-otp-sent');

        Notification::assertSentTo($user, EmailVerificationOtpNotification::class);
    }

    public function test_verification_code_resend_is_rate_limited(): void
    {
        Notification::fake();

        $user = User::factory()->unverified()->create();
        app(\App\Services\Auth\EmailVerificationOtpService::class)->issue($user);

        $this->actingAs($user)
            ->from('/verify-email')
            ->post('/email/verification-notification')
            ->assertRedirect('/verify-email')
            ->assertSessionHasErrors('resend');

        Notification::assertSentTimes(EmailVerificationOtpNotification::class, 1);
    }

    public function test_verification_code_resend_works_after_cooldown(): void
    {
        Notification::fake();

        $user = User::factory()->unverified()->create();
        app(\App\Services\Auth\EmailVerificationOtpService::class)->issue($user);

        $this->travel(\App\Services\Auth\EmailVerificationOtpService::RESEND_COOLDOWN_SECONDS + 1)->seconds();

        $this->actingAs($user)->post('/email/verification-notification')
            ->assertRedirect()
            ->assertSessionHas('status', 'verification-otp-sent');

        Notification::assertSentTimes(EmailVerificationOtpNotification::class, 2);
    }
}
