<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\UserResource;
use App\Models\User;
use App\Services\Api\UserCapabilities;
use App\Services\Auth\EmailVerificationOtpService;
use Illuminate\Auth\Events\Lockout;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Laravel\Sanctum\PersonalAccessToken;

class AuthController extends Controller
{
    public function login(Request $request): JsonResponse
    {
        $data = $request->validate([
            'email' => ['required', 'string', 'email'],
            'password' => ['required', 'string'],
            'device_name' => ['nullable', 'string', 'max:255'],
        ]);

        $this->ensureLoginIsNotRateLimited($data['email'], $request->ip());

        /** @var User|null $user */
        $user = User::query()->where('email', $data['email'])->first();

        if (! $user || ! Hash::check($data['password'], $user->password)) {
            RateLimiter::hit($this->loginThrottleKey($data['email'], $request->ip()));

            throw ValidationException::withMessages([
                'email' => [trans('auth.failed')],
            ]);
        }

        RateLimiter::clear($this->loginThrottleKey($data['email'], $request->ip()));

        $deviceName = $data['device_name'] ?? 'mobile';
        $token = $user->createToken($deviceName)->plainTextToken;

        return response()->json([
            'data' => [
                'token' => $token,
                'token_type' => 'Bearer',
                'user' => new UserResource($user),
                'capabilities' => app(UserCapabilities::class)->for($user),
            ],
        ]);
    }

    public function user(Request $request, UserCapabilities $capabilities): JsonResponse
    {
        $user = $request->user();

        return response()->json([
            'data' => [
                'user' => new UserResource($user),
                'capabilities' => $capabilities->for($user),
            ],
        ]);
    }

    public function logout(Request $request): JsonResponse
    {
        PersonalAccessToken::findToken($request->bearerToken() ?? '')?->delete();

        return response()->json([
            'data' => [
                'message' => 'Logged out.',
            ],
        ]);
    }

    public function verifyEmail(Request $request, EmailVerificationOtpService $otp): JsonResponse
    {
        $data = $request->validate([
            'code' => ['required', 'string', 'min:6', 'max:6'],
        ]);

        $user = $request->user();
        $otp->verify($user, $data['code']);

        return response()->json([
            'data' => [
                'message' => 'Email verified.',
                'user' => new UserResource($user->fresh()),
            ],
        ]);
    }

    public function resendVerification(Request $request, EmailVerificationOtpService $otp): JsonResponse
    {
        $user = $request->user();

        if ($user->hasVerifiedEmail()) {
            return response()->json([
                'data' => [
                    'message' => 'Email is already verified.',
                ],
            ]);
        }

        $otp->resend($user);

        return response()->json([
            'data' => [
                'message' => 'Verification code sent.',
                'resend_available_at' => $otp->resendAvailableAt($user)?->toIso8601String(),
                'expires_at' => $otp->activeExpiry($user)?->toIso8601String(),
            ],
        ]);
    }

    /**
     * @throws ValidationException
     */
    private function ensureLoginIsNotRateLimited(string $email, ?string $ip): void
    {
        $key = $this->loginThrottleKey($email, $ip);

        if (! RateLimiter::tooManyAttempts($key, 5)) {
            return;
        }

        event(new Lockout(request()));

        $seconds = RateLimiter::availableIn($key);

        throw ValidationException::withMessages([
            'email' => [trans('auth.throttle', [
                'seconds' => $seconds,
                'minutes' => ceil($seconds / 60),
            ])],
        ]);
    }

    private function loginThrottleKey(string $email, ?string $ip): string
    {
        return Str::transliterate(Str::lower($email).'|'.($ip ?? 'unknown'));
    }
}
