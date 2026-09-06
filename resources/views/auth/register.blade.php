@php
    $journal ??= null;
    $registerAction = $journal ? route('journals.register.store', $journal) : route('register');
    $loginLink = $journal
        ? route('journals.login', $journal)
        : route('login');
@endphp
<x-guest-layout :title="$journal ? 'Enrol · '.$journal->title : null" :journal="$journal">
    <div class="mb-6">
        @if($journal)
            <p class="text-xs font-bold uppercase tracking-[0.16em] text-[var(--muted)]">{{ $journal->title }}</p>
            <h1 class="serif mt-2 text-2xl font-bold text-[var(--ink)]">Enrol</h1>
            <p class="auth-muted mt-2">Create your account for this journal. We will email you a verification code after sign-up.@if($membershipPlan ?? null) After verification, you will be redirected to complete the {{ $membershipPlan->name }} payment (₦{{ number_format($membershipPlan->price_amount) }}).@elseif($freeEnrollment ?? false) After verification, you will become a member of this journal at no charge.@endif</p>
        @else
            <h1 class="serif text-2xl font-bold text-[var(--ink)]">Create your account</h1>
            <p class="auth-muted mt-2">Sign up without joining a journal first — then create your own journal from the dashboard, or enrol in one later.</p>
        @endif
    </div>

    <form method="POST" action="{{ $registerAction }}" class="space-y-4">
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

        <button type="submit" class="auth-btn mt-2">{{ $journal ? 'Enrol' : 'Sign up' }}</button>
    </form>

    <div class="auth-footer">
        @if($journal)
            <div class="auth-footer__primary">
                <div class="auth-footer__primary-copy">
                    <strong>Already enrolled?</strong>
                    <span>Sign in to continue with this journal.</span>
                </div>
                <a href="{{ $loginLink }}" class="auth-footer__cta">Log in</a>
            </div>
            <p class="auth-footer__alt">
                Or use
                <a href="{{ route('register') }}">platform sign up</a>
            </p>
        @else
            <div class="auth-footer__primary">
                <div class="auth-footer__primary-copy">
                    <strong>Already have an account?</strong>
                    <span>Sign in to your TJS account.</span>
                </div>
                <a href="{{ $loginLink }}" class="auth-footer__cta">Log in</a>
            </div>
            <p class="auth-footer__alt">
                Joining an existing journal?
                <a href="{{ route('register.journals', array_filter(['redirect' => $redirect ?? null])) }}">Enrol in a journal</a>
            </p>
        @endif
    </div>
</x-guest-layout>
