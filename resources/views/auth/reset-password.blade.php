<x-guest-layout>
    <div class="mb-6">
        <h1 class="serif text-2xl font-bold text-[var(--ink)]">Choose a new password</h1>
        <p class="auth-muted mt-2">Set a strong password for your TJS account.</p>
    </div>

    <form method="POST" action="{{ route('password.store') }}" class="space-y-4">
        @csrf
        <input type="hidden" name="token" value="{{ $request->route('token') }}">

        <div>
            <label class="auth-label" for="email">Email</label>
            <input id="email" class="auth-input" type="email" name="email" value="{{ old('email', $request->email) }}" required autofocus autocomplete="username">
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

        <button type="submit" class="auth-btn">Reset password</button>
    </form>
</x-guest-layout>
