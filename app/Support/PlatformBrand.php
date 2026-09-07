<?php

namespace App\Support;

final class PlatformBrand
{
    /**
     * @return non-empty-string
     */
    public static function asset(string $key): string
    {
        $path = config("tjs.brand.{$key}") ?? config('tjs.brand_icon', 'images/brand/tjs-icon-light.png');

        return asset($path);
    }

    /**
     * @return non-empty-string
     */
    public static function logo(bool $onDarkBackground = false, bool $iconOnly = false): string
    {
        if ($iconOnly) {
            return self::asset($onDarkBackground ? 'icon_dark' : 'icon_light');
        }

        return self::asset($onDarkBackground ? 'logo_dark' : 'logo_light');
    }
}
