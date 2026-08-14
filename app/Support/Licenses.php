<?php

namespace App\Support;

class Licenses
{
    /**
     * Standard selectable licenses (label stored on articles/journals).
     *
     * @return array<string, array{label: string, url: string, description: string}>
     */
    public static function catalog(): array
    {
        return [
            'cc-by-4.0' => [
                'label' => 'CC BY 4.0',
                'url' => 'https://creativecommons.org/licenses/by/4.0/',
                'description' => 'Attribution 4.0 International',
            ],
            'cc-by-sa-4.0' => [
                'label' => 'CC BY-SA 4.0',
                'url' => 'https://creativecommons.org/licenses/by-sa/4.0/',
                'description' => 'Attribution-ShareAlike 4.0 International',
            ],
            'cc-by-nc-4.0' => [
                'label' => 'CC BY-NC 4.0',
                'url' => 'https://creativecommons.org/licenses/by-nc/4.0/',
                'description' => 'Attribution-NonCommercial 4.0 International',
            ],
            'cc-by-nc-nd-4.0' => [
                'label' => 'CC BY-NC-ND 4.0',
                'url' => 'https://creativecommons.org/licenses/by-nc-nd/4.0/',
                'description' => 'Attribution-NonCommercial-NoDerivatives 4.0 International',
            ],
            'all-rights-reserved' => [
                'label' => 'All rights reserved',
                'url' => 'https://en.wikipedia.org/wiki/All_rights_reserved',
                'description' => 'No reuse without explicit permission',
            ],
        ];
    }

    /**
     * @return list<string>
     */
    public static function labels(): array
    {
        return array_values(array_map(fn (array $item) => $item['label'], self::catalog()));
    }

    /**
     * @return list<array{key: string, label: string, url: string, description: string}>
     */
    public static function options(): array
    {
        $out = [];
        foreach (self::catalog() as $key => $item) {
            $out[] = [
                'key' => $key,
                'label' => $item['label'],
                'url' => $item['url'],
                'description' => $item['description'],
            ];
        }

        return $out;
    }

    public static function find(?string $value): ?array
    {
        $value = trim((string) $value);
        if ($value === '') {
            return null;
        }

        $lower = strtolower($value);
        foreach (self::catalog() as $key => $item) {
            if ($key === $lower || strtolower($item['label']) === $lower || strtolower($item['url']) === $lower) {
                return ['key' => $key] + $item;
            }
        }

        // Soft match common OCR / shorthand forms.
        $normalized = preg_replace('/\s+/', ' ', strtoupper(str_replace(['_', '/'], [' ', ' '], $value))) ?: '';
        $aliases = [
            'CC BY' => 'cc-by-4.0',
            'CC-BY' => 'cc-by-4.0',
            'CC BY 4.0' => 'cc-by-4.0',
            'CC-BY-4.0' => 'cc-by-4.0',
            'CREATIVE COMMONS ATTRIBUTION 4.0' => 'cc-by-4.0',
            'CC BY-SA' => 'cc-by-sa-4.0',
            'CC-BY-SA' => 'cc-by-sa-4.0',
            'CC BY-SA 4.0' => 'cc-by-sa-4.0',
            'CC BY-NC' => 'cc-by-nc-4.0',
            'CC-BY-NC' => 'cc-by-nc-4.0',
            'CC BY-NC 4.0' => 'cc-by-nc-4.0',
            'CC BY-NC-ND' => 'cc-by-nc-nd-4.0',
            'CC-BY-NC-ND' => 'cc-by-nc-nd-4.0',
            'CC BY-NC-ND 4.0' => 'cc-by-nc-nd-4.0',
            'ALL RIGHTS RESERVED' => 'all-rights-reserved',
            'ARR' => 'all-rights-reserved',
        ];

        if (isset($aliases[$normalized])) {
            $key = $aliases[$normalized];
            $item = self::catalog()[$key];

            return ['key' => $key] + $item;
        }

        return null;
    }

    public static function label(?string $value): ?string
    {
        return self::find($value)['label'] ?? null;
    }

    public static function url(?string $value): ?string
    {
        return self::find($value)['url'] ?? null;
    }

    /**
     * Canonical label for storage, or null if not in the standard set.
     */
    public static function normalize(?string $value): ?string
    {
        return self::label($value);
    }

    public static function rule(): string
    {
        return 'in:'.implode(',', self::labels());
    }
}
