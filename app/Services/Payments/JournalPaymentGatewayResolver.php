<?php

namespace App\Services\Payments;

use App\Models\Journal;
use App\Models\PaymentTransaction;
use RuntimeException;

class JournalPaymentGatewayResolver
{
    public const PREF_PERSONAL = 'personal';

    public const PREF_SPLIT = 'split';

    public const PREF_UNSET = 'unset';

    /**
     * Always platform keys (activation fees, platform membership).
     */
    public function platform(): PaymentGatewayContext
    {
        $secret = (string) config('paystack.secret_key');
        if ($secret === '') {
            throw new RuntimeException('Platform Paystack is not configured. Add PAYSTACK_SECRET_KEY.');
        }

        return new PaymentGatewayContext(
            mode: PaymentGatewayContext::MODE_PLATFORM,
            secretKey: $secret,
            publicKey: config('paystack.public_key'),
        );
    }

    /**
     * Resolve gateway for journal-scoped income (articles / journal memberships).
     *
     * Order: preferred personal (if allowed) → split → error.
     */
    public function forJournalIncome(Journal $journal): PaymentGatewayContext
    {
        $preference = $journal->payment_gateway_preference ?: self::PREF_UNSET;
        $personalAllowed = (bool) $journal->personal_gateway_allowed;
        $hasPersonal = $this->hasPersonalKeys($journal);
        $hasSplit = $this->hasSplitConfig($journal);

        $wantPersonal = $preference === self::PREF_PERSONAL
            || ($preference === self::PREF_UNSET && $hasPersonal);

        if ($wantPersonal && $personalAllowed && $hasPersonal) {
            return new PaymentGatewayContext(
                mode: PaymentGatewayContext::MODE_PERSONAL,
                secretKey: (string) $journal->readEncrypted('paystack_secret_key'),
                publicKey: $journal->readEncrypted('paystack_public_key'),
                journal: $journal,
            );
        }

        $wantSplit = $preference === self::PREF_SPLIT
            || ($wantPersonal && ! $personalAllowed)
            || ($preference === self::PREF_UNSET && ! $hasPersonal);

        if (($wantSplit || ($wantPersonal && ! $personalAllowed)) && $hasSplit) {
            $platform = $this->platform();

            return new PaymentGatewayContext(
                mode: PaymentGatewayContext::MODE_SPLIT,
                secretKey: $platform->secretKey,
                publicKey: $platform->publicKey,
                splitCode: $journal->paystack_split_code,
                journal: $journal,
            );
        }

        if ($wantPersonal && ! $personalAllowed && ! $hasSplit) {
            throw new JournalPaymentUnavailableException(
                'Personal gateway disabled by platform and no split code configured'
            );
        }

        if ($preference === self::PREF_PERSONAL && ! $hasPersonal) {
            throw new JournalPaymentUnavailableException(
                'Personal Paystack preference selected but keys are not configured'
            );
        }

        if ($preference === self::PREF_SPLIT && ! $hasSplit) {
            throw new JournalPaymentUnavailableException(
                'Split payment preference selected but no Paystack split code configured'
            );
        }

        throw new JournalPaymentUnavailableException(
            'No personal keys or split code available for journal income'
        );
    }

    public function hasPersonalKeys(Journal $journal): bool
    {
        return filled($journal->readEncrypted('paystack_secret_key'))
            && filled($journal->readEncrypted('paystack_public_key'));
    }

    public function hasSplitConfig(Journal $journal): bool
    {
        return filled($journal->paystack_split_code);
    }

    public function resolvedModeLabel(Journal $journal): string
    {
        try {
            return $this->forJournalIncome($journal)->mode;
        } catch (RuntimeException) {
            return 'unavailable';
        }
    }

    public function contextFromTransaction(PaymentTransaction $tx): PaymentGatewayContext
    {
        $mode = $tx->gateway_mode ?: PaymentGatewayContext::MODE_PLATFORM;
        $journal = $tx->relationLoaded('gatewayJournal')
            ? $tx->gatewayJournal
            : $tx->gatewayJournal()->first();

        if ($mode === PaymentGatewayContext::MODE_PERSONAL && $journal && $this->hasPersonalKeys($journal)) {
            return new PaymentGatewayContext(
                mode: PaymentGatewayContext::MODE_PERSONAL,
                secretKey: (string) $journal->readEncrypted('paystack_secret_key'),
                publicKey: $journal->readEncrypted('paystack_public_key'),
                journal: $journal,
            );
        }

        if ($mode === PaymentGatewayContext::MODE_SPLIT && $journal && $this->hasSplitConfig($journal)) {
            $platform = $this->platform();

            return new PaymentGatewayContext(
                mode: PaymentGatewayContext::MODE_SPLIT,
                secretKey: $platform->secretKey,
                publicKey: $platform->publicKey,
                splitCode: $journal->paystack_split_code,
                journal: $journal,
            );
        }

        return $this->platform();
    }

    /**
     * @return list<string>
     */
    public function webhookCandidateSecrets(?PaymentTransaction $tx): array
    {
        $secrets = [];
        $platform = (string) (config('paystack.webhook_secret') ?: config('paystack.secret_key'));
        if ($platform !== '') {
            $secrets[] = $platform;
        }

        if ($tx?->gateway_mode === PaymentGatewayContext::MODE_PERSONAL) {
            $journal = $tx->gatewayJournal;
            $secret = $journal?->readEncrypted('paystack_secret_key');

            if ($journal && filled($secret)) {
                $secrets[] = (string) $secret;
            }
        }

        // Unknown reference: also try all journal personal secrets would be expensive.
        // Prefer reference lookup first; if missing, platform secret only.

        return array_values(array_unique(array_filter($secrets)));
    }
}
