<?php

namespace App\Http\Controllers\JournalManage;

use App\Http\Controllers\Controller;
use App\Models\Journal;
use App\Services\Payments\JournalPaymentGatewayResolver;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class PaymentGatewayController extends Controller
{
    public function __construct(private JournalPaymentGatewayResolver $gateways)
    {
    }

    public function edit(Journal $journal): View
    {
        $publicKey = $journal->readEncrypted('paystack_public_key');
        $hasPersonal = $this->gateways->hasPersonalKeys($journal);
        $personalKeysCorrupted = $journal->anyEncryptedAttributesCorrupted([
            'paystack_public_key',
            'paystack_secret_key',
        ]);

        return view('journal-manage.payments.gateway', [
            'journal' => $journal,
            'canMutate' => $journal->userMayMutate(auth()->user()),
            'hasPersonal' => $hasPersonal,
            'personalKeysCorrupted' => $personalKeysCorrupted,
            'maskedPublicKey' => $hasPersonal
                ? Str::mask($publicKey, '•', 6, max(0, strlen($publicKey) - 10))
                : '',
            'hasSplit' => $this->gateways->hasSplitConfig($journal),
            'resolvedMode' => $this->gateways->resolvedModeLabel($journal),
            'webhookUrl' => url('/paystack/webhook'),
        ]);
    }

    public function update(Request $request, Journal $journal): RedirectResponse
    {
        abort_unless($journal->userMayMutate($request->user()), 403);

        $data = $request->validate([
            'payment_gateway_preference' => ['required', Rule::in(['personal', 'split', 'unset'])],
            'paystack_public_key' => ['nullable', 'string', 'max:255'],
            'paystack_secret_key' => ['nullable', 'string', 'max:255'],
            'paystack_split_code' => ['nullable', 'string', 'max:64', 'regex:/^SPL_[A-Za-z0-9]+$/i'],
            'clear_personal_keys' => ['sometimes', 'boolean'],
            'clear_split_code' => ['sometimes', 'boolean'],
        ]);

        $journal->payment_gateway_preference = $data['payment_gateway_preference'];

        if ($request->boolean('clear_personal_keys')) {
            $journal->paystack_public_key = null;
            $journal->paystack_secret_key = null;
        } else {
            $public = trim((string) ($data['paystack_public_key'] ?? ''));
            $secret = trim((string) ($data['paystack_secret_key'] ?? ''));

            if ($public !== '' && ! str_contains($public, '•')) {
                $journal->paystack_public_key = $public;
            }
            if ($secret !== '' && ! str_contains($secret, '•')) {
                $journal->paystack_secret_key = $secret;
            }
        }

        if ($request->boolean('clear_split_code')) {
            $journal->paystack_split_code = null;
        } else {
            $split = strtoupper(trim((string) ($data['paystack_split_code'] ?? '')));
            if ($split !== '') {
                $journal->paystack_split_code = $split;
            }
        }

        $journal->save();
        $journal->refresh();

        if ($journal->payment_gateway_preference === 'personal' && ! $this->gateways->hasPersonalKeys($journal)) {
            return back()
                ->withInput()
                ->withErrors(['paystack_secret_key' => 'Personal gateway requires both public and secret Paystack keys.']);
        }

        if ($journal->payment_gateway_preference === 'split' && ! $this->gateways->hasSplitConfig($journal)) {
            return back()
                ->withInput()
                ->withErrors(['paystack_split_code' => 'Split mode requires a Paystack split code (SPL_…).']);
        }

        return redirect()
            ->route('journal.manage.payments.gateway', $journal)
            ->with('status', 'Payment gateway settings saved.');
    }
}
