<?php

namespace App\Support;

use Illuminate\Http\Request;

final class ListLayout
{
    public const GRID = 'grid';

    public const LIST = 'list';

    public static function fromRequest(Request $request, string $default = self::GRID): string
    {
        $view = $request->string('view')->toString();

        return in_array($view, [self::GRID, self::LIST], true) ? $view : $default;
    }

    public static function toggleUrl(Request $request, string $view): string
    {
        return $request->fullUrlWithQuery([
            'view' => $view,
            'page' => null,
        ]);
    }
}
