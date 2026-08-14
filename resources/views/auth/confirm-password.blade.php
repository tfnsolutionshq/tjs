<x-guest-layout>
    <div class="mb-6">
        <h1 class="serif text-2xl font-bold text-[var(--ink)]">Confirm password</h1>
        <p class="auth-muted mt-2">This is a secure area. Please confirm your password to continue.</p>
    </div>

    <form method="POST" action="{{ route('password.confirm') }}" class="space-y-4">
        @csrf
        <div>
            <label class="auth-label" for="password">Password</label>
            <input id="password" class="auth-input" type="password" name="password" required autocomplete="current-password">
            <x-input-error :messages="$errors->get('password')" class="auth-error" />
        </div>
        <button type="submit" class="auth-btn">Confirm</button>
    </form>
</x-guest-layout>
