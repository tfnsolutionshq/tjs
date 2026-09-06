<x-guest-layout title="Enrol in a journal" :wide="true">
    <div class="mb-5">
        <h1 class="serif text-2xl font-bold text-[var(--ink)]">Enrol in a journal</h1>
        <p class="auth-muted mt-2">Search for a journal to join, or create a platform account and launch your own later.</p>
    </div>

    @include('auth.partials.journal-picker', [
        'featuredJournals' => $featuredJournals,
        'otherJournalsCount' => $otherJournalsCount,
        'totalJournalsCount' => $totalJournalsCount,
        'redirect' => $redirect ?? null,
        'pickerAction' => $pickerAction ?? 'register',
        'emptyMessage' => 'No journals are open for enrolment right now. Please check back later.',
        'emptyCtaRoute' => route('register'),
        'emptyCtaLabel' => 'Platform sign up',
        'platformRoute' => route('register', array_filter(['redirect' => $redirect ?? null])),
        'platformPrompt' => 'Prefer a platform account?',
        'platformLinkLabel' => 'Sign up here',
    ])
</x-guest-layout>
