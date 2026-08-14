<x-guest-layout>
    <div class="mb-6">
        <h1 class="serif text-2xl font-bold text-[var(--ink)]">Welcome back</h1>
        <p class="auth-muted mt-2">Log in to submit manuscripts, review papers, or manage journals.</p>
    </div>

    <form
        method="POST"
        action="{{ route('login') }}"
        class="space-y-4"
        x-data="{ submitting: false }"
        @submit="if (submitting) { $event.preventDefault() } else { submitting = true }"
    >
        @csrf

        <div>
            <label class="auth-label" for="email">Email</label>
            <input id="email" class="auth-input" type="email" name="email" value="{{ old('email') }}" required autofocus autocomplete="username" :readonly="submitting">
            <x-input-error :messages="$errors->get('email')" class="auth-error" />
        </div>

        <div>
            <div class="mb-1 flex items-center justify-between">
                <label class="auth-label !mb-0" for="password">Password</label>
                @if (Route::has('password.request'))
                    <a class="auth-link text-xs" href="{{ route('password.request') }}">Forgot password?</a>
                @endif
            </div>
            <input id="password" class="auth-input" type="password" name="password" required autocomplete="current-password" :readonly="submitting">
            <x-input-error :messages="$errors->get('password')" class="auth-error" />
        </div>

        <label for="remember_me" class="inline-flex items-center gap-2 text-sm" style="color:var(--muted)">
            <input id="remember_me" type="checkbox" class="rounded border-slate-300 text-[var(--blue)] focus:ring-[var(--blue)]" name="remember" @click="if (submitting) $event.preventDefault()">
            Remember me
        </label>

        <button
            type="submit"
            class="auth-btn auth-btn-green mt-2"
            :class="submitting && 'is-loading'"
            :disabled="submitting"
            :aria-busy="submitting"
        >
            <span class="auth-btn__spinner" x-show="submitting" x-cloak></span>
            <span x-text="submitting ? 'Logging in…' : 'Log in'"></span>
        </button>
    </form>

    <p class="auth-muted mt-6 text-center text-sm">
        New to TJS?
        <a href="{{ route('register') }}" class="auth-link">Create an account</a>
    </p>
</x-guest-layout>
