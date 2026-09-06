<?php

namespace App\Support;

use App\Models\Setting;
use Throwable;

class PlatformSettings
{
    /**
     * @return array<string, mixed>
     */
    public static function defaults(): array
    {
        return [
            'name' => config('tjs.name'),
            'full_name' => config('tjs.full_name'),
            'organization' => config('tjs.organization'),
            'publisher' => config('tjs.publisher'),
            'default_license' => config('tjs.default_license'),
            'default_language' => config('tjs.default_language'),
            'currency' => config('tjs.currency'),
            'membership_platform_enabled' => (bool) config('tjs.membership.platform_enabled', true),
            'membership_platform_price' => (int) config('tjs.membership.platform_price'),
            'membership_platform_days' => (int) config('tjs.membership.platform_days'),
            'journal_activation_enabled' => (bool) config('tjs.journal_activation.enabled', true),
            'journal_activation_price' => (int) config('tjs.journal_activation.price'),
            'journal_activation_days' => (int) config('tjs.journal_activation.days'),
            'payments_split_fee_percent' => (int) config('tjs.payments.split_fee_percent', 0),
            'doi_enabled' => (bool) config('tjs.doi.enabled', true),
            'doi_usd_to_ngn' => (int) config('tjs.doi.usd_to_ngn', 2000),
            'doi_credit_price_usd' => (float) config('tjs.doi.credit_price_usd', 1),
            'doi_threshold_absolute' => (int) config('tjs.doi.threshold_absolute', 20),
            'doi_threshold_percent' => (int) config('tjs.doi.threshold_percent', 10),
            'doi_platform_prefix' => (string) config('tjs.doi.platform_prefix', '10.0000/tjs'),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public static function current(): array
    {
        $defaults = static::defaults();

        try {
            $stored = Setting::allCached();
        } catch (Throwable) {
            return $defaults;
        }

        return [
            'name' => $stored['name'] ?? $defaults['name'],
            'full_name' => $stored['full_name'] ?? $defaults['full_name'],
            'organization' => $stored['organization'] ?? $defaults['organization'],
            'publisher' => $stored['publisher'] ?? $defaults['publisher'],
            'default_license' => $stored['default_license'] ?? $defaults['default_license'],
            'default_language' => $stored['default_language'] ?? $defaults['default_language'],
            'currency' => $stored['currency'] ?? $defaults['currency'],
            'membership_platform_enabled' => static::toBool(
                $stored['membership_platform_enabled'] ?? $defaults['membership_platform_enabled']
            ),
            'membership_platform_price' => max(0, (int) ($stored['membership_platform_price'] ?? $defaults['membership_platform_price'])),
            'membership_platform_days' => max(1, (int) ($stored['membership_platform_days'] ?? $defaults['membership_platform_days'])),
            'journal_activation_enabled' => static::toBool(
                $stored['journal_activation_enabled'] ?? $defaults['journal_activation_enabled']
            ),
            'journal_activation_price' => max(0, (int) ($stored['journal_activation_price'] ?? $defaults['journal_activation_price'])),
            'journal_activation_days' => max(1, (int) ($stored['journal_activation_days'] ?? $defaults['journal_activation_days'])),
            'payments_split_fee_percent' => max(0, min(100, (int) ($stored['payments_split_fee_percent'] ?? $defaults['payments_split_fee_percent']))),
            'doi_enabled' => static::toBool($stored['doi_enabled'] ?? $defaults['doi_enabled']),
            'doi_usd_to_ngn' => max(1, (int) ($stored['doi_usd_to_ngn'] ?? $defaults['doi_usd_to_ngn'])),
            'doi_credit_price_usd' => max(0, (float) ($stored['doi_credit_price_usd'] ?? $defaults['doi_credit_price_usd'])),
            'doi_threshold_absolute' => max(0, (int) ($stored['doi_threshold_absolute'] ?? $defaults['doi_threshold_absolute'])),
            'doi_threshold_percent' => max(0, min(100, (int) ($stored['doi_threshold_percent'] ?? $defaults['doi_threshold_percent']))),
            'doi_platform_prefix' => (string) ($stored['doi_platform_prefix'] ?? $defaults['doi_platform_prefix']),
        ];
    }

    public static function applyToConfig(): void
    {
        try {
            $current = static::current();
        } catch (Throwable) {
            return;
        }

        config([
            'tjs.name' => $current['name'],
            'tjs.full_name' => $current['full_name'],
            'tjs.organization' => $current['organization'],
            'tjs.publisher' => $current['publisher'],
            'tjs.default_license' => $current['default_license'],
            'tjs.default_language' => $current['default_language'],
            'tjs.currency' => $current['currency'],
            'tjs.membership.platform_enabled' => $current['membership_platform_enabled'],
            'tjs.membership.platform_price' => $current['membership_platform_price'],
            'tjs.membership.platform_days' => $current['membership_platform_days'],
            'tjs.journal_activation.enabled' => $current['journal_activation_enabled'],
            'tjs.journal_activation.price' => $current['journal_activation_price'],
            'tjs.journal_activation.days' => $current['journal_activation_days'],
            'tjs.payments.split_fee_percent' => $current['payments_split_fee_percent'],
            'tjs.doi.enabled' => $current['doi_enabled'],
            'tjs.doi.usd_to_ngn' => $current['doi_usd_to_ngn'],
            'tjs.doi.credit_price_usd' => $current['doi_credit_price_usd'],
            'tjs.doi.threshold_absolute' => $current['doi_threshold_absolute'],
            'tjs.doi.threshold_percent' => $current['doi_threshold_percent'],
            'tjs.doi.platform_prefix' => $current['doi_platform_prefix'],
        ]);
    }

    private static function toBool(mixed $value): bool
    {
        if (is_bool($value)) {
            return $value;
        }

        return filter_var((string) $value, FILTER_VALIDATE_BOOLEAN);
    }
}
