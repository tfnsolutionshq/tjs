<?php

namespace App\Services\Payments;

use App\Models\Journal;

class PaymentGatewayContext
{
    public const MODE_PLATFORM = 'platform';

    public const MODE_PERSONAL = 'personal';

    public const MODE_SPLIT = 'split';

    public function __construct(
        public readonly string $mode,
        public readonly string $secretKey,
        public readonly ?string $publicKey = null,
        public readonly ?string $splitCode = null,
        public readonly ?Journal $journal = null,
    ) {
    }

    public function isJournalIncome(): bool
    {
        return $this->mode === self::MODE_PERSONAL || $this->mode === self::MODE_SPLIT;
    }
}
