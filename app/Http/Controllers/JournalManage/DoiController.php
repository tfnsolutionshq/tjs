<?php

namespace App\Http\Controllers\JournalManage;

use App\Http\Controllers\Controller;
use App\Models\Article;
use App\Models\DoiDeposit;
use App\Models\Journal;
use App\Services\Doi\DoiCreditService;
use App\Services\Doi\DoiDepositService;
use App\Support\DoiSettings;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class DoiController extends Controller
{
    public function __construct(
        private DoiCreditService $credits,
        private DoiDepositService $deposits,
    ) {
    }

    public function index(Journal $journal): View
    {
        $deposits = DoiDeposit::query()
            ->with(['article:id,slug,title', 'depositor:id,name'])
            ->where('journal_id', $journal->id)
            ->orderByDesc('id')
            ->paginate(20);

        return view('journal-manage.doi.index', [
            'journal' => $journal,
            'canMutate' => $journal->userMayMutate(auth()->user()),
            'banner' => $this->credits->statusBanner($journal),
            'deposits' => $deposits,
            'rate' => DoiSettings::usdToNgn(),
            'priceNgn' => DoiSettings::creditPriceNgn(),
            'platformPrefix' => DoiSettings::platformPrefix(),
            'enabled' => DoiSettings::enabled(),
            'hasCrossrefCredentials' => filled($crossrefUsername = $journal->readEncrypted('crossref_username')),
            'maskedCrossrefUsername' => filled($crossrefUsername)
                ? Str::mask($crossrefUsername, '•', 2, max(0, strlen($crossrefUsername) - 4))
                : '',
            'crossrefCredentialsCorrupted' => $journal->anyEncryptedAttributesCorrupted([
                'crossref_username',
                'crossref_password',
            ]),
        ]);
    }

    public function updateSettings(Request $request, Journal $journal): RedirectResponse
    {
        abort_unless($journal->userMayMutate($request->user()), 403);

        $data = $request->validate([
            'doi_mode' => ['required', Rule::in(['unset', 'platform', 'own'])],
            'doi_prefix' => ['nullable', 'string', 'max:120'],
            'crossref_username' => ['nullable', 'string', 'max:120'],
            'crossref_password' => ['nullable', 'string', 'max:255'],
            'doi_auto_deposit' => ['sometimes', 'boolean'],
            'clear_crossref' => ['sometimes', 'boolean'],
        ]);

        $journal->doi_mode = $data['doi_mode'];
        $journal->doi_auto_deposit = $request->boolean('doi_auto_deposit');

        if ($data['doi_mode'] === 'own') {
            $prefix = trim((string) ($data['doi_prefix'] ?? ''));
            $journal->doi_prefix = $prefix !== '' ? rtrim($prefix, '/') : null;

            if ($request->boolean('clear_crossref')) {
                $journal->crossref_username = null;
                $journal->crossref_password = null;
            } else {
                $user = trim((string) ($data['crossref_username'] ?? ''));
                $pass = (string) ($data['crossref_password'] ?? '');
                if ($user !== '' && ! str_contains($user, '•')) {
                    $journal->crossref_username = $user;
                }
                if ($pass !== '' && ! str_contains($pass, '•')) {
                    $journal->crossref_password = $pass;
                }
            }
        }

        $journal->save();
        $journal->refresh();

        if ($journal->doi_mode === 'own') {
            if (! filled($journal->doi_prefix)) {
                return back()->withErrors(['doi_prefix' => 'Own Crossref mode requires your DOI prefix.'])->withInput();
            }
            if (! filled($journal->readEncrypted('crossref_username')) || ! filled($journal->readEncrypted('crossref_password'))) {
                return back()->withErrors(['crossref_username' => 'Own Crossref mode requires username and password.'])->withInput();
            }
        }

        return redirect()
            ->route('journal.manage.doi.index', $journal)
            ->with('status', 'DOI settings saved.');
    }

    public function deposit(Request $request, Journal $journal, Article $article): RedirectResponse
    {
        abort_unless($journal->userMayMutate($request->user()), 403);
        abort_unless((int) $article->journal_id === (int) $journal->id, 404);

        $data = $request->validate([
            'doi' => ['nullable', 'string', 'max:255'],
        ]);

        try {
            $deposit = $this->deposits->depositArticle(
                $article,
                $request->user(),
                $data['doi'] ?? null,
                autoMint: true,
            );

            return back()->with('status', 'DOI deposited: '.$deposit->doi);
        } catch (\Throwable $e) {
            return back()->with('error', $e->getMessage());
        }
    }
}
