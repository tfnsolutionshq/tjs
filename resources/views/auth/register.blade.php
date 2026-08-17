<x-guest-layout>
    <div class="mb-6">
        <h1 class="serif text-2xl font-bold text-[var(--ink)]">Create your account</h1>
        <p class="auth-muted mt-2">Join TFN Journal System to submit and access scholarly articles. We will email you a verification code after sign-up.</p>
    </div>

    <form method="POST" action="{{ route('register') }}" class="space-y-4">
        @csrf

        <div>
            <x-form-label class="auth-label" for="name" field="auth.name">Full name</x-form-label>
            <input id="name" class="auth-input" type="text" name="name" value="{{ old('name') }}" required autofocus autocomplete="name">
            <x-input-error :messages="$errors->get('name')" class="auth-error" />
        </div>

        <div>
            <x-form-label class="auth-label" for="email" field="auth.email">Email</x-form-label>
            <input id="email" class="auth-input" type="email" name="email" value="{{ old('email') }}" required autocomplete="username">
            <x-input-error :messages="$errors->get('email')" class="auth-error" />
        </div>

        <div>
            <x-form-label class="auth-label" for="password" field="auth.password">Password</x-form-label>
            <x-password-input id="password" name="password" class="auth-input" required autocomplete="new-password" />
            <x-input-error :messages="$errors->get('password')" class="auth-error" />
        </div>

        <div>
            <x-form-label class="auth-label" for="password_confirmation" field="user.password_confirmation">Confirm password</x-form-label>
            <x-password-input id="password_confirmation" name="password_confirmation" class="auth-input" required autocomplete="new-password" />
            <x-input-error :messages="$errors->get('password_confirmation')" class="auth-error" />
        </div>

        <button type="submit" class="auth-btn mt-2">Sign up</button>
    </form>

    <p class="auth-muted mt-6 text-center text-sm">
        Already have an account?
        <a href="{{ route('login') }}" class="auth-link">Log in</a>
    </p>
</x-guest-layout>
