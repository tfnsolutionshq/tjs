<?php

namespace App\Support;

final class DoiSettings
{
    public static function enabled(): bool
    {
        return (bool) config('tjs.doi.enabled', true);
    }

    public static function usdToNgn(): int
    {
        return max(1, (int) config('tjs.doi.usd_to_ngn', 2000));
    }

    public static function creditPriceUsd(): float
    {
        return max(0, (float) config('tjs.doi.credit_price_usd', 1));
    }

    public static function creditPriceNgn(): int
    {
        return (int) round(self::creditPriceUsd() * self::usdToNgn());
    }

    public static function thresholdAbsolute(): int
    {
        return max(0, (int) config('tjs.doi.threshold_absolute', 20));
    }

    public static function thresholdPercent(): int
    {
        return max(0, min(100, (int) config('tjs.doi.threshold_percent', 10)));
    }

    public static function platformPrefix(): string
    {
        return rtrim((string) config('tjs.doi.platform_prefix', '10.0000/tjs'), '/');
    }

    /**
     * @return array{username:?string,password:?string,deposit_url:string,depositor_name:string,depositor_email:string,registrant:string}
     */
    public static function platformCrossref(): array
    {
        return [
            'username' => config('tjs.doi.crossref.username'),
            'password' => config('tjs.doi.crossref.password'),
            'deposit_url' => (string) config('tjs.doi.crossref.deposit_url'),
            'depositor_name' => (string) config('tjs.doi.crossref.depositor_name'),
            'depositor_email' => (string) config('tjs.doi.crossref.depositor_email'),
            'registrant' => (string) config('tjs.doi.crossref.registrant'),
        ];
    }

    public static function isLowBalance(int $balance, int $lifetime): bool
    {
        if ($balance <= 0) {
            return false;
        }

        if ($balance <= self::thresholdAbsolute()) {
            return true;
        }

        if ($lifetime > 0 && self::thresholdPercent() > 0) {
            $floor = (int) ceil($lifetime * (self::thresholdPercent() / 100));

            return $balance <= max(1, $floor);
        }

        return false;
    }
}
