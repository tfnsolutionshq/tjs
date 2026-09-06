<?php

namespace App\Support;

use App\Models\Journal;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

class JournalAuth
{
    /**
     * @return Collection<int, Journal>
     */
    public static function activeJournals(): Collection
    {
        return Journal::query()
            ->listed()
            ->orderByDesc('is_featured')
            ->orderBy('title')
            ->get(['id', 'slug', 'title', 'subtitle', 'initials', 'logo_path', 'logo_disk', 'is_featured', 'updated_at']);
    }

    public static function ensureActive(Journal $journal): void
    {
        abort_unless($journal->isListed(), 404);
    }

    /**
     * Remember where to send the user after login / email verification.
     */
    public static function captureIntended(Request $request, Journal $journal): void
    {
        self::ensureActive($journal);

        $redirect = (string) $request->query('redirect', '');
        if ($redirect !== '' && str_starts_with($redirect, url('/'))) {
            $request->session()->put('url.intended', $redirect);

            return;
        }

        $request->session()->put('url.intended', route('journals.show', $journal));
    }

    /**
     * Append a safe redirect query when linking from the platform picker.
     *
     * @param  array<string, mixed>  $parameters
     * @return array<string, mixed>
     */
    public static function withRedirectQuery(Request $request, array $parameters = []): array
    {
        $redirect = (string) $request->query('redirect', '');
        if ($redirect !== '' && str_starts_with($redirect, url('/'))) {
            $parameters['redirect'] = $redirect;
        }

        return $parameters;
    }
}
