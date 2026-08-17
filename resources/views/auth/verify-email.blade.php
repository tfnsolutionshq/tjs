<x-guest-layout>
    <div class="mb-6">
        <h1 class="serif text-2xl font-bold text-[var(--ink)]">Verify your email</h1>
        <p class="auth-muted mt-2">
            We sent a 6-digit code to
            <strong style="color:var(--ink)">{{ auth()->user()->email }}</strong>.
            Enter it below to activate your account.
        </p>
    </div>

    <div
        x-data="verifyEmailPage(@js($otpExpiresAt), @js($otpResendAvailableAt ?? null))"
        x-init="start()"
    >
        <form method="POST" action="{{ route('verification.otp') }}" class="space-y-4">
            @csrf

            <div>
                <x-form-label class="auth-label" for="code">Verification code</x-form-label>
                <input
                    id="code"
                    class="auth-input text-center tracking-[.35em] font-semibold text-lg"
                    type="text"
                    name="code"
                    value="{{ old('code') }}"
                    required
                    autofocus
                    autocomplete="one-time-code"
                    inputmode="numeric"
                    pattern="[0-9]{6}"
                    maxlength="6"
                    placeholder="000000"
                >
                <x-input-error :messages="$errors->get('code')" class="auth-error" />
                <p class="auth-muted mt-2 text-xs" x-show="codeRemaining > 0" x-cloak>
                    Code expires in <strong style="color:var(--ink)" x-text="codeFormatted"></strong>
                </p>
                <p class="auth-muted mt-2 text-xs" x-show="codeRemaining <= 0 && !hasCodeExpiry" x-cloak>
                    Request a code using the button below if you have not received one yet.
                </p>
                <p class="auth-muted mt-2 text-xs" x-show="codeRemaining <= 0 && hasCodeExpiry" x-cloak>
                    This code has expired. Use <strong>Resend code</strong> below to get a new one.
                </p>
            </div>

            <button type="submit" class="auth-btn mt-2">Verify email</button>
        </form>

        <div class="mt-6 flex flex-col gap-3 border-t border-slate-200 pt-5">
            <form id="verification-resend-form" method="POST" action="{{ route('verification.send') }}">
                @csrf
                <button
                    type="submit"
                    class="auth-btn auth-btn-secondary w-full disabled:opacity-50 disabled:cursor-not-allowed"
                    :disabled="resendRemaining > 0"
                >
                    <span x-show="resendRemaining <= 0">Resend code</span>
                    <span x-show="resendRemaining > 0" x-cloak>Resend in <span x-text="resendFormatted"></span></span>
                </button>
                <x-input-error :messages="$errors->get('resend')" class="auth-error mt-2" />
            </form>

            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" class="auth-link w-full text-center text-sm">Log out</button>
            </form>
        </div>
    </div>

    <script>
        function verifyEmailPage(codeExpiresIso, resendAvailableIso) {
            const secondsLeft = (iso) => {
                if (!iso) {
                    return 0;
                }

                return Math.max(0, Math.floor((new Date(iso).getTime() - Date.now()) / 1000));
            };

            const format = (totalSeconds) => {
                const minutes = Math.floor(totalSeconds / 60);
                const seconds = totalSeconds % 60;

                return `${minutes}:${String(seconds).padStart(2, '0')}`;
            };

            return {
                codeRemaining: secondsLeft(codeExpiresIso),
                codeFormatted: format(secondsLeft(codeExpiresIso)),
                hasCodeExpiry: !!codeExpiresIso,
                resendRemaining: secondsLeft(resendAvailableIso),
                resendFormatted: format(secondsLeft(resendAvailableIso)),
                timer: null,
                start() {
                    const tick = () => {
                        this.codeRemaining = secondsLeft(codeExpiresIso);
                        this.codeFormatted = format(this.codeRemaining);
                        this.resendRemaining = secondsLeft(resendAvailableIso);
                        this.resendFormatted = format(this.resendRemaining);

                        if (this.codeRemaining <= 0 && this.resendRemaining <= 0 && this.timer) {
                            clearInterval(this.timer);
                            this.timer = null;
                        }
                    };

                    tick();

                    if (codeExpiresIso || resendAvailableIso) {
                        this.timer = setInterval(tick, 1000);
                    }
                },
            };
        }
    </script>
</x-guest-layout>
