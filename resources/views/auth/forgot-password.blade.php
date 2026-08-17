<x-guest-layout>
    <div class="mb-6">
        <h1 class="serif text-2xl font-bold text-[var(--ink)]">Reset your password</h1>
        <p class="auth-muted mt-2">Enter your email and we’ll send a reset link.</p>
    </div>

    <form method="POST" action="{{ route('password.email') }}" class="space-y-4">
        @csrf

        <div>
            <x-form-label class="auth-label" for="email" field="auth.email">Email</x-form-label>
            <input id="email" class="auth-input" type="email" name="email" value="{{ old('email') }}" required autofocus>
            <x-input-error :messages="$errors->get('email')" class="auth-error" />
        </div>

        <button type="submit" class="auth-btn">Email reset link</button>
    </form>

    <p class="auth-muted mt-6 text-center text-sm">
        <a href="{{ route('login') }}" class="auth-link">Back to login</a>
    </p>
</x-guest-layout>
