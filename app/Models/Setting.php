<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class Setting extends Model
{
    protected $fillable = ['key', 'value'];

    public static function getValue(string $key, mixed $default = null): mixed
    {
        $all = static::allCached();

        return array_key_exists($key, $all) ? $all[$key] : $default;
    }

    public static function putValue(string $key, mixed $value): void
    {
        static::query()->updateOrCreate(
            ['key' => $key],
            ['value' => is_bool($value) ? ($value ? '1' : '0') : (string) $value]
        );

        Cache::forget('tjs.settings');
    }

    public static function putMany(array $pairs): void
    {
        foreach ($pairs as $key => $value) {
            static::query()->updateOrCreate(
                ['key' => $key],
                ['value' => is_bool($value) ? ($value ? '1' : '0') : (string) ($value ?? '')]
            );
        }

        Cache::forget('tjs.settings');
    }

    /**
     * @return array<string, string|null>
     */
    public static function allCached(): array
    {
        return Cache::remember('tjs.settings', 600, function () {
            return static::query()->pluck('value', 'key')->all();
        });
    }

    public static function flushCache(): void
    {
        Cache::forget('tjs.settings');
    }
}
