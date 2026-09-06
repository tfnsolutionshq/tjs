<?php

namespace App\Support;

final class JournalFeePurpose
{
    public const SUBMISSION = 'submission';

    public const MEMBERSHIP = 'membership';

    public const ARTICLE = 'article';

    /**
     * @return list<string>
     */
    public static function all(): array
    {
        return [
            self::SUBMISSION,
            self::MEMBERSHIP,
            self::ARTICLE,
        ];
    }

    public static function label(string $purpose): string
    {
        return match ($purpose) {
            self::SUBMISSION => 'Submission fee',
            self::MEMBERSHIP => 'Membership fee',
            self::ARTICLE => 'Publication fee (APC)',
            default => ucfirst($purpose),
        };
    }

    public static function description(string $purpose): string
    {
        return match ($purpose) {
            self::SUBMISSION => 'Charged when authors submit manuscripts.',
            self::MEMBERSHIP => 'Used when creating membership products.',
            self::ARTICLE => 'Charged for publication (APC) or paid article pricing.',
            default => '',
        };
    }
}
