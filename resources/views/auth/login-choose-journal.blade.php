<x-guest-layout title="Log in via a journal" :wide="true">
    <div class="mb-5">
        <h1 class="serif text-2xl font-bold text-[var(--ink)]">Log in via a journal</h1>
        <p class="auth-muted mt-2">Search and choose the journal site you want to open after signing in.</p>
    </div>

    @include('auth.partials.journal-picker', [
        'featuredJournals' => $featuredJournals,
        'otherJournalsCount' => $otherJournalsCount,
        'totalJournalsCount' => $totalJournalsCount,
        'redirect' => $redirect ?? null,
        'pickerAction' => $pickerAction ?? 'login',
        'emptyMessage' => 'No journals are open for login right now. Please check back later.',
        'emptyCtaRoute' => route('login'),
        'emptyCtaLabel' => 'Platform login',
        'platformRoute' => route('login', array_filter(['redirect' => $redirect ?? null])),
        'platformPrompt' => 'Prefer the platform?',
        'platformLinkLabel' => 'Log in here',
    ])
</x-guest-layout>
