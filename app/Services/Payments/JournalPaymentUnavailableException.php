<?php

namespace App\Services\Payments;

use RuntimeException;

class JournalPaymentUnavailableException extends RuntimeException
{
    public const USER_MESSAGE = 'This journal is not ready to receive payments yet. Please try again later.';

    public function __construct(
        private readonly string $internalReason = '',
    ) {
        parent::__construct(self::USER_MESSAGE);
    }

    public function internalReason(): string
    {
        return $this->internalReason;
    }
}
