<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Models\Journal;
use App\Support\JournalAuth;
use App\Services\Journal\JournalEnrollmentService;
use App\Services\Journal\JournalPickerService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class AuthenticatedSessionController extends Controller
{
    /**
     * Platform login (account without journal enrolment).
     */
    public function create(Request $request): View
    {
        $redirect = (string) $request->query('redirect', '');
        if ($redirect !== '' && str_starts_with($redirect, url('/'))) {
            $request->session()->put('url.intended', $redirect);
        }

        return view('auth.login', [
            'journal' => null,
            'redirect' => $redirect !== '' && str_starts_with($redirect, url('/')) ? $redirect : null,
        ]);
    }

    /**
     * Platform journal picker: continue login under a specific journal.
     */
    public function chooseJournal(Request $request, JournalPickerService $picker): View
    {
        $redirect = (string) $request->query('redirect', '');
        if ($redirect !== '' && str_starts_with($redirect, url('/'))) {
            $request->session()->put('url.intended', $redirect);
        }

        $safeRedirect = $redirect !== '' && str_starts_with($redirect, url('/')) ? $redirect : null;

        return view('auth.login-choose-journal', [
            'featuredJournals' => $picker->featured(),
            'otherJournalsCount' => $picker->otherCount(),
            'totalJournalsCount' => $picker->totalCount(),
            'redirect' => $safeRedirect,
            'pickerAction' => 'login',
        ]);
    }

    /**
     * Handle an incoming platform authentication request.
     */
    public function store(LoginRequest $request): RedirectResponse
    {
        $request->authenticate();

        $request->session()->regenerate();

        $home = route(
            $request->user()->homeRouteName(),
            $request->user()->homeRouteParameters(),
            absolute: false
        );

        return redirect()->intended($home);
    }

    /**
     * Journal-branded login form.
     */
    public function createForJournal(Request $request, Journal $journal): View
    {
        JournalAuth::captureIntended($request, $journal);

        return view('auth.login', [
            'journal' => $journal,
        ]);
    }

    /**
     * Handle an incoming journal authentication request.
     */
    public function storeForJournal(LoginRequest $request, Journal $journal, JournalEnrollmentService $enrollment): RedirectResponse
    {
        JournalAuth::ensureActive($journal);

        if (! $request->session()->has('url.intended')) {
            $request->session()->put('url.intended', route('journals.show', $journal));
        }

        $request->authenticate();

        $request->session()->regenerate();

        $user = $request->user();
        $paidPlan = $enrollment->pendingPaidPlanForUser($user, (int) $journal->id);

        if ($paidPlan) {
            return redirect()
                ->route('memberships.checkout', $paidPlan)
                ->with('status', 'Complete your membership payment to access members-only content for this journal.');
        }

        $membership = $enrollment->ensureJournalAccess($user, $journal);

        $home = route(
            $user->homeRouteName(),
            $user->homeRouteParameters(),
            absolute: false
        );

        return redirect()
            ->intended($home)
            ->with('status', $membership ? 'You are now a member of '.$journal->title.'.' : null);
    }

    /**
     * Destroy an authenticated session.
     */
    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();

        $request->session()->regenerateToken();

        return redirect('/');
    }
}
