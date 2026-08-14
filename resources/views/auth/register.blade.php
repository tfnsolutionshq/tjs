<x-guest-layout>
    <div class="mb-6">
        <h1 class="serif text-2xl font-bold text-[var(--ink)]">Create your account</h1>
        <p class="auth-muted mt-2">Join TFN Journal System to submit and access scholarly articles.</p>
    </div>

    <form method="POST" action="{{ route('register') }}" class="space-y-4">
        @csrf

        <div>
            <label class="auth-label" for="name">Full name</label>
            <input id="name" class="auth-input" type="text" name="name" value="{{ old('name') }}" required autofocus autocomplete="name">
            <x-input-error :messages="$errors->get('name')" class="auth-error" />
        </div>

        <div>
            <label class="auth-label" for="email">Email</label>
            <input id="email" class="auth-input" type="email" name="email" value="{{ old('email') }}" required autocomplete="username">
            <x-input-error :messages="$errors->get('email')" class="auth-error" />
        </div>

        <div>
            <label class="auth-label" for="password">Password</label>
            <input id="password" class="auth-input" type="password" name="password" required autocomplete="new-password">
            <x-input-error :messages="$errors->get('password')" class="auth-error" />
        </div>

        <div>
            <label class="auth-label" for="password_confirmation">Confirm password</label>
            <input id="password_confirmation" class="auth-input" type="password" name="password_confirmation" required autocomplete="new-password">
            <x-input-error :messages="$errors->get('password_confirmation')" class="auth-error" />
        </div>

        <button type="submit" class="auth-btn mt-2">Sign up</button>
    </form>

    <p class="auth-muted mt-6 text-center text-sm">
        Already have an account?
        <a href="{{ route('login') }}" class="auth-link">Log in</a>
    </p>
</x-guest-layout>
