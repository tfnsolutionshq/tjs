<?php

namespace App\Support;

use App\Models\Setting;
use Carbon\Carbon;
use Illuminate\Support\Str;

final class SiteNotice
{
    public const TTL_DAYS = 7;

    /**
     * @return array{message: string, style: string, expires_at: Carbon}|null
     */
    public static function active(): ?array
    {
        if (Setting::getValue('site_notice_enabled', '0') !== '1') {
            return null;
        }

        $message = trim((string) Setting::getValue('site_notice_message', ''));
        if ($message === '') {
            return null;
        }

        $setAtRaw = Setting::getValue('site_notice_set_at');
        if ($setAtRaw) {
            $setAt = Carbon::parse($setAtRaw);
            if (now()->greaterThan($setAt->copy()->addDays(self::TTL_DAYS))) {
                static::clear();

                return null;
            }
        }

        $style = (string) Setting::getValue('site_notice_style', 'info');
        if (! in_array($style, ['info', 'warning', 'success'], true)) {
            $style = 'info';
        }

        return [
            'message' => $message,
            'style' => $style,
            'expires_at' => $setAtRaw
                ? Carbon::parse($setAtRaw)->addDays(self::TTL_DAYS)
                : now()->addDays(self::TTL_DAYS),
        ];
    }

    /**
     * @return array{enabled: bool, message: string, style: string, set_at: ?string}
     */
    public static function adminFormState(): array
    {
        return [
            'enabled' => Setting::getValue('site_notice_enabled', '0') === '1',
            'message' => (string) Setting::getValue('site_notice_message', ''),
            'style' => (string) Setting::getValue('site_notice_style', 'info'),
            'set_at' => Setting::getValue('site_notice_set_at'),
        ];
    }

    public static function save(bool $enabled, string $message, string $style = 'info'): void
    {
        $message = trim($message);
        $style = in_array($style, ['info', 'warning', 'success'], true) ? $style : 'info';

        if (! $enabled || $message === '') {
            static::clear();

            return;
        }

        $previousMessage = trim((string) Setting::getValue('site_notice_message', ''));
        $wasEnabled = Setting::getValue('site_notice_enabled', '0') === '1';

        $pairs = [
            'site_notice_enabled' => '1',
            'site_notice_message' => $message,
            'site_notice_style' => $style,
        ];

        if (! $wasEnabled || $message !== $previousMessage) {
            $pairs['site_notice_set_at'] = now()->toIso8601String();
        }

        Setting::putMany($pairs);
    }

    public static function clear(): void
    {
        Setting::putMany([
            'site_notice_enabled' => '0',
            'site_notice_message' => '',
            'site_notice_style' => 'info',
            'site_notice_set_at' => '',
        ]);
    }

    public static function adminHint(?string $setAt): ?string
    {
        if (! $setAt) {
            return null;
        }

        try {
            $expires = Carbon::parse($setAt)->addDays(self::TTL_DAYS);
        } catch (\Throwable) {
            return null;
        }

        if (now()->greaterThan($expires)) {
            return 'This notice has expired and will no longer appear on the site.';
        }

        return 'Visible until '.$expires->format('M j, Y g:ia').' ('.Str::lower($expires->diffForHumans()).').';
    }
}
