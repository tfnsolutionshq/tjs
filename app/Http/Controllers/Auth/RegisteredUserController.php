<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Journal;
use App\Models\User;
use App\Support\JournalAuth;
use App\Services\Journal\JournalEnrollmentService;
use App\Services\Journal\JournalPickerService;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class RegisteredUserController extends Controller
{
    /**
     * Platform registration (create an account without enrolling in a journal).
     */
    public function create(Request $request): View
    {
        $redirect = (string) $request->query('redirect', '');
        if ($redirect !== '' && str_starts_with($redirect, url('/'))) {
            $request->session()->put('url.intended', $redirect);
        }

        return view('auth.register', [
            'journal' => null,
            'redirect' => $redirect !== '' && str_starts_with($redirect, url('/')) ? $redirect : null,
        ]);
    }

    /**
     * Platform journal picker: enrol under a specific journal.
     */
    public function chooseJournal(Request $request, JournalPickerService $picker): View
    {
        $redirect = (string) $request->query('redirect', '');
        if ($redirect !== '' && str_starts_with($redirect, url('/'))) {
            $request->session()->put('url.intended', $redirect);
        }

        $safeRedirect = $redirect !== '' && str_starts_with($redirect, url('/')) ? $redirect : null;

        return view('auth.register-choose-journal', [
            'featuredJournals' => $picker->featured(),
            'otherJournalsCount' => $picker->otherCount(),
            'totalJournalsCount' => $picker->totalCount(),
            'redirect' => $safeRedirect,
            'pickerAction' => 'register',
        ]);
    }

    /**
     * Handle an incoming platform registration request.
     *
     * @throws ValidationException
     */
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:'.User::class],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
        ]);

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'role' => 'member',
        ]);

        event(new Registered($user));

        Auth::login($user);

        return redirect(route('verification.notice', absolute: false));
    }

    /**
     * Journal-branded registration form.
     */
    public function createForJournal(Request $request, Journal $journal, JournalEnrollmentService $enrollment): View
    {
        JournalAuth::captureIntended($request, $journal);

        return view('auth.register', [
            'journal' => $journal,
            'membershipPlan' => $enrollment->activePaidPlanForJournal((int) $journal->id),
            'freeEnrollment' => ! $enrollment->requiresPaidMembership($journal),
        ]);
    }

    /**
     * Handle an incoming journal registration request.
     *
     * @throws ValidationException
     */
    public function storeForJournal(Request $request, Journal $journal, JournalEnrollmentService $enrollment): RedirectResponse
    {
        JournalAuth::ensureActive($journal);

        if (! $request->session()->has('url.intended')) {
            $request->session()->put('url.intended', route('journals.show', $journal));
        }

        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:'.User::class],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
        ]);

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'role' => 'member',
        ]);

        event(new Registered($user));

        Auth::login($user);

        $enrollment->rememberJournalForMembership($request, $journal);

        return redirect(route('verification.notice', absolute: false));
    }
}
