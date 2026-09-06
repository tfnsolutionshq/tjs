@php
    $journal ??= null;
    $loginAction = $journal ? route('journals.login.store', $journal) : route('login');
    $registerLink = $journal
        ? route('journals.register', $journal)
        : route('register');
@endphp
<x-guest-layout :title="$journal ? 'Log in · '.$journal->title : null" :journal="$journal">
    <div class="mb-6">
        @if($journal)
            <p class="text-xs font-bold uppercase tracking-[0.16em] text-[var(--muted)]">{{ $journal->title }}</p>
            <h1 class="serif mt-2 text-2xl font-bold text-[var(--ink)]">Log in</h1>
            <p class="auth-muted mt-2">Sign in to submit, review, or manage work for this journal.</p>
        @else
            <h1 class="serif text-2xl font-bold text-[var(--ink)]">Welcome back</h1>
            <p class="auth-muted mt-2">Log in to your TJS account — then create a journal, submit manuscripts, or manage your work.</p>
        @endif
    </div>

    <form
        method="POST"
        action="{{ $loginAction }}"
        class="space-y-4"
        x-data="{ submitting: false }"
        @submit="if (submitting) { $event.preventDefault() } else { submitting = true }"
    >
        @csrf

        <div>
            <x-form-label class="auth-label" for="email" field="auth.email">Email</x-form-label>
            <input id="email" class="auth-input" type="email" name="email" value="{{ old('email') }}" required autofocus autocomplete="username" :readonly="submitting">
            <x-input-error :messages="$errors->get('email')" class="auth-error" />
        </div>

        <div>
            <div class="mb-1 flex items-center justify-between">
                <x-form-label class="auth-label !mb-0" for="password" field="auth.password">Password</x-form-label>
                @if (Route::has('password.request'))
                    <a class="auth-link text-xs" href="{{ route('password.request') }}">Forgot password?</a>
                @endif
            </div>
            <x-password-input id="password" name="password" class="auth-input" required autocomplete="current-password" x-bind:readonly="submitting" />
            <x-input-error :messages="$errors->get('password')" class="auth-error" />
        </div>

        <label for="remember_me" class="inline-flex items-center gap-2 text-sm" style="color:var(--muted)">
            <input id="remember_me" type="checkbox" class="rounded border-slate-300 text-[var(--blue)] focus:ring-[var(--blue)]" name="remember" @click="if (submitting) $event.preventDefault()">
            Remember me
            <x-field-helper :text="\App\Support\FormHelp::get('auth.remember')" />
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

    <div class="auth-footer">
        @if($journal)
            <div class="auth-footer__primary">
                <div class="auth-footer__primary-copy">
                    <strong>New to this journal?</strong>
                    <span>Create an account to enrol and continue.</span>
                </div>
                <a href="{{ $registerLink }}" class="auth-footer__cta">Enrol</a>
            </div>
            <p class="auth-footer__alt">
                Or use
                <a href="{{ route('login') }}">platform login</a>
            </p>
        @else
            <div class="auth-footer__primary">
                <div class="auth-footer__primary-copy">
                    <strong>New to TJS?</strong>
                    <span>Create a free account to get started.</span>
                </div>
                <a href="{{ $registerLink }}" class="auth-footer__cta">Create account</a>
            </div>
            <p class="auth-footer__alt">
                Prefer a journal site?
                <a href="{{ route('login.journals', array_filter(['redirect' => $redirect ?? null])) }}">Log in via a journal</a>
            </p>
        @endif
    </div>
</x-guest-layout>
