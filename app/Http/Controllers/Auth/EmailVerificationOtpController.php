<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Services\Auth\EmailVerificationOtpService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class EmailVerificationOtpController extends Controller
{
    public function store(Request $request, EmailVerificationOtpService $otp): RedirectResponse
    {
        $data = $request->validate([
            'code' => ['required', 'string', 'min:6', 'max:6'],
        ]);

        $otp->verify($request->user(), $data['code']);

        return redirect()->intended(
            route($request->user()->homeRouteName(), $request->user()->homeRouteParameters(), absolute: false).'?verified=1'
        );
    }
}
