<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Services\Auth\EmailVerificationOtpService;
use App\Services\Journal\JournalEnrollmentService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class EmailVerificationPromptController extends Controller
{
    /**
     * Display the email verification prompt.
     */
    public function __invoke(Request $request, EmailVerificationOtpService $otp, JournalEnrollmentService $enrollment): RedirectResponse|View
    {
        if ($request->user()->hasVerifiedEmail()) {
            return $enrollment->afterVerificationRedirect($request);
        }

        return view('auth.verify-email', [
            'otpExpiresAt' => $otp->activeExpiry($request->user())?->toIso8601String(),
            'otpResendAvailableAt' => $otp->resendAvailableAt($request->user())?->toIso8601String(),
        ]);
    }
}
