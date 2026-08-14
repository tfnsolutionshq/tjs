<?php

namespace App\Support;

use App\Models\Journal;
use Illuminate\Support\Facades\Route;

class JournalPortal
{
    public static function current(): ?Journal
    {
        $journal = request()->route('journal');

        return $journal instanceof Journal ? $journal : null;
    }

    public static function active(): bool
    {
        return self::current() !== null && Route::is('journal.manage.*');
    }

    /**
     * @param  array<string, mixed>  $params
     */
    public static function route(string $name, array $params = [], bool $absolute = true): string
    {
        $journal = self::current();
        if ($journal && self::active()) {
            return route('journal.manage.'.$name, array_merge(['journal' => $journal], $params), $absolute);
        }

        return route('admin.'.$name, $params, $absolute);
    }
}
