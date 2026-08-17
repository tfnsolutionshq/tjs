<?php

namespace App\Support;

final class Nationalities
{
    /** @var list<array{code: string, name: string}>|null */
    private static ?array $all = null;

    /**
     * @return list<array{code: string, name: string}>
     */
    public static function all(): array
    {
        if (self::$all !== null) {
            return self::$all;
        }

        $path = resource_path('data/nationalities.json');
        $decoded = is_file($path) ? json_decode((string) file_get_contents($path), true) : null;

        if (! is_array($decoded)) {
            self::$all = [];

            return self::$all;
        }

        self::$all = array_values(array_filter(array_map(static function ($row): ?array {
            if (! is_array($row)) {
                return null;
            }

            $code = strtolower(trim((string) ($row['code'] ?? '')));
            $name = trim((string) ($row['name'] ?? ''));

            if ($code === '' || $name === '') {
                return null;
            }

            return ['code' => $code, 'name' => $name];
        }, $decoded)));

        return self::$all;
    }

    /**
     * @return array{code: string, name: string}|null
     */
    public static function findByCode(string $code): ?array
    {
        $code = strtolower(trim($code));

        foreach (self::all() as $country) {
            if ($country['code'] === $code) {
                return $country;
            }
        }

        return null;
    }

    /**
     * @return array{code: string, name: string}|null
     */
    public static function findByName(string $name): ?array
    {
        $name = trim($name);
        if ($name === '') {
            return null;
        }

        foreach (self::all() as $country) {
            if (strcasecmp($country['name'], $name) === 0) {
                return $country;
            }
        }

        return null;
    }

    /**
     * Match a stored or extracted value to a known nationality.
     *
     * @return array{code: string, name: string}|null
     */
    public static function match(string $value): ?array
    {
        $value = trim($value);
        if ($value === '') {
            return null;
        }

        $exact = self::findByName($value);
        if ($exact) {
            return $exact;
        }

        $needle = strtolower($value);
        $best = null;
        foreach (self::all() as $country) {
            $name = strtolower($country['name']);
            if (! str_contains($needle, $name) && ! str_contains($name, $needle)) {
                continue;
            }

            if ($best === null || strlen($country['name']) > strlen($best['name'])) {
                $best = $country;
            }
        }

        return $best;
    }

    public static function normalize(?string $value): ?string
    {
        $value = trim((string) $value);
        if ($value === '') {
            return null;
        }

        return self::match($value)['name'] ?? $value;
    }

    public static function flagSvgUrl(string $countryCode): string
    {
        $code = strtolower(preg_replace('/[^a-z]/i', '', $countryCode) ?: 'un');

        return 'https://flagcdn.com/'.$code.'.svg';
    }

    /**
     * @return list<array{code: string, name: string, flag_url: string}>
     */
    public static function forPicker(): array
    {
        return array_map(static function (array $country): array {
            return [
                ...$country,
                'flag_url' => self::flagSvgUrl($country['code']),
            ];
        }, self::all());
    }

    /**
     * @return list<string>
     */
    public static function names(): array
    {
        return array_column(self::all(), 'name');
    }
}
