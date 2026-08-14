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
            'membership_platform_price' => (int) config('tjs.membership.platform_price'),
            'membership_platform_days' => (int) config('tjs.membership.platform_days'),
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
            'membership_platform_price' => (int) ($stored['membership_platform_price'] ?? $defaults['membership_platform_price']),
            'membership_platform_days' => (int) ($stored['membership_platform_days'] ?? $defaults['membership_platform_days']),
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
            'tjs.membership.platform_price' => $current['membership_platform_price'],
            'tjs.membership.platform_days' => $current['membership_platform_days'],
        ]);
    }
}
