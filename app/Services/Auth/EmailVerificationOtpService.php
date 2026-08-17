<?php

namespace App\Services\Auth;

use App\Models\EmailVerificationCode;
use App\Models\User;
use App\Notifications\EmailVerificationOtpNotification;
use Illuminate\Auth\Events\Verified;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class EmailVerificationOtpService
{
    public const CODE_LENGTH = 6;

    public const EXPIRY_MINUTES = 15;

    public const MAX_ATTEMPTS = 5;

    public const RESEND_COOLDOWN_SECONDS = 60;

    public function issue(User $user): void
    {
        EmailVerificationCode::query()->where('user_id', $user->id)->delete();

        $code = $this->generateCode();

        EmailVerificationCode::query()->create([
            'user_id' => $user->id,
            'code_hash' => Hash::make($code),
            'expires_at' => now()->addMinutes(self::EXPIRY_MINUTES),
        ]);

        $user->notify(new EmailVerificationOtpNotification($code));
    }

    public function resend(User $user): void
    {
        $this->assertCanResend($user);

        $this->issue($user);
    }

    public function resendAvailableAt(User $user): ?\Illuminate\Support\Carbon
    {
        $record = EmailVerificationCode::query()
            ->where('user_id', $user->id)
            ->latest('id')
            ->first();

        if (! $record) {
            return null;
        }

        $availableAt = $record->created_at->addSeconds(self::RESEND_COOLDOWN_SECONDS);

        return $availableAt->isFuture() ? $availableAt : null;
    }

    public function assertCanResend(User $user): void
    {
        $availableAt = $this->resendAvailableAt($user);

        if (! $availableAt) {
            return;
        }

        $seconds = max(1, $availableAt->diffInSeconds(now()));

        throw ValidationException::withMessages([
            'resend' => "Please wait {$seconds} seconds before requesting another code.",
        ]);
    }

    public function activeExpiry(User $user): ?\Illuminate\Support\Carbon
    {
        $record = EmailVerificationCode::query()
            ->where('user_id', $user->id)
            ->latest('id')
            ->first();

        if (! $record || $record->expires_at->isPast()) {
            return null;
        }

        return $record->expires_at;
    }

    public function verify(User $user, string $code): void
    {
        $record = EmailVerificationCode::query()
            ->where('user_id', $user->id)
            ->latest('id')
            ->first();

        if (! $record || $record->expires_at->isPast()) {
            throw ValidationException::withMessages([
                'code' => 'This verification code has expired. Request a new one.',
            ]);
        }

        if ($record->attempts >= self::MAX_ATTEMPTS) {
            throw ValidationException::withMessages([
                'code' => 'Too many failed attempts. Request a new code.',
            ]);
        }

        $normalized = $this->normalizeCode($code);

        if (strlen($normalized) !== self::CODE_LENGTH || ! Hash::check($normalized, $record->code_hash)) {
            $record->increment('attempts');

            throw ValidationException::withMessages([
                'code' => 'The verification code is incorrect.',
            ]);
        }

        if (! $user->hasVerifiedEmail()) {
            if ($user->markEmailAsVerified()) {
                event(new Verified($user));
            }
        }

        EmailVerificationCode::query()->where('user_id', $user->id)->delete();
    }

    private function generateCode(): string
    {
        return str_pad(
            (string) random_int(0, (10 ** self::CODE_LENGTH) - 1),
            self::CODE_LENGTH,
            '0',
            STR_PAD_LEFT
        );
    }

    private function normalizeCode(string $code): string
    {
        return preg_replace('/\D/', '', $code) ?? '';
    }
}
