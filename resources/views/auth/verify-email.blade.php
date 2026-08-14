<x-guest-layout>
    <div class="mb-6">
        <h1 class="serif text-2xl font-bold text-[var(--ink)]">Verify your email</h1>
        <p class="auth-muted mt-2">
            Thanks for signing up. Click the link we emailed you, or request another verification email below.
        </p>
    </div>

    <div class="flex flex-col gap-3">
        <form method="POST" action="{{ route('verification.send') }}">
            @csrf
            <button type="submit" class="auth-btn">Resend verification email</button>
        </form>

        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit" class="auth-link w-full text-center text-sm">Log out</button>
        </form>
    </div>
</x-guest-layout>
