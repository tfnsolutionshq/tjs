<?php

namespace App\Support;

final class JournalActivation
{
    public const STATUS_UNPAID = 'unpaid';

    public const STATUS_ACTIVE = 'active';

    public const STATUS_EXPIRED = 'expired';

    /**
     * Whether the platform is currently collecting journal activation fees.
     */
    public static function enabled(): bool
    {
        return (bool) config('tjs.journal_activation.enabled', true);
    }

    /**
     * Whether new journals must pay before listing / full management unlock.
     */
    public static function required(): bool
    {
        return self::enabled() && self::price() > 0;
    }

    /**
     * Reminder offsets in days before expiry (0 = expired day).
     *
     * @return list<int>
     */
    public static function reminderDays(): array
    {
        $days = config('tjs.journal_activation.reminder_days', [90, 60, 30, 7, 1, 0]);

        return array_values(array_map('intval', $days));
    }

    public static function price(): int
    {
        return max(0, (int) config('tjs.journal_activation.price', 50000));
    }

    public static function durationDays(): int
    {
        return max(1, (int) config('tjs.journal_activation.days', 365));
    }

    public static function currency(): string
    {
        return (string) config('tjs.currency', 'NGN');
    }
}
