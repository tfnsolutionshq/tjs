<?php

namespace App\Support;

final class JournalLanguages
{
    /**
     * Common journal languages with ISO 639-1 codes and ISO 3166-1 flag regions.
     *
     * @return list<array{code: string, name: string, flag: string}>
     */
    public static function all(): array
    {
        return [
            ['code' => 'en', 'name' => 'English', 'flag' => 'gb'],
            ['code' => 'fr', 'name' => 'French', 'flag' => 'fr'],
            ['code' => 'es', 'name' => 'Spanish', 'flag' => 'es'],
            ['code' => 'pt', 'name' => 'Portuguese', 'flag' => 'pt'],
            ['code' => 'de', 'name' => 'German', 'flag' => 'de'],
            ['code' => 'it', 'name' => 'Italian', 'flag' => 'it'],
            ['code' => 'nl', 'name' => 'Dutch', 'flag' => 'nl'],
            ['code' => 'ar', 'name' => 'Arabic', 'flag' => 'sa'],
            ['code' => 'zh', 'name' => 'Chinese', 'flag' => 'cn'],
            ['code' => 'ja', 'name' => 'Japanese', 'flag' => 'jp'],
            ['code' => 'ko', 'name' => 'Korean', 'flag' => 'kr'],
            ['code' => 'ru', 'name' => 'Russian', 'flag' => 'ru'],
            ['code' => 'hi', 'name' => 'Hindi', 'flag' => 'in'],
            ['code' => 'bn', 'name' => 'Bengali', 'flag' => 'bd'],
            ['code' => 'ur', 'name' => 'Urdu', 'flag' => 'pk'],
            ['code' => 'tr', 'name' => 'Turkish', 'flag' => 'tr'],
            ['code' => 'fa', 'name' => 'Persian', 'flag' => 'ir'],
            ['code' => 'sw', 'name' => 'Swahili', 'flag' => 'tz'],
            ['code' => 'ha', 'name' => 'Hausa', 'flag' => 'ng'],
            ['code' => 'yo', 'name' => 'Yoruba', 'flag' => 'ng'],
            ['code' => 'ig', 'name' => 'Igbo', 'flag' => 'ng'],
            ['code' => 'pl', 'name' => 'Polish', 'flag' => 'pl'],
            ['code' => 'uk', 'name' => 'Ukrainian', 'flag' => 'ua'],
            ['code' => 'cs', 'name' => 'Czech', 'flag' => 'cz'],
            ['code' => 'ro', 'name' => 'Romanian', 'flag' => 'ro'],
            ['code' => 'hu', 'name' => 'Hungarian', 'flag' => 'hu'],
            ['code' => 'sv', 'name' => 'Swedish', 'flag' => 'se'],
            ['code' => 'da', 'name' => 'Danish', 'flag' => 'dk'],
            ['code' => 'fi', 'name' => 'Finnish', 'flag' => 'fi'],
            ['code' => 'no', 'name' => 'Norwegian', 'flag' => 'no'],
            ['code' => 'el', 'name' => 'Greek', 'flag' => 'gr'],
            ['code' => 'he', 'name' => 'Hebrew', 'flag' => 'il'],
            ['code' => 'th', 'name' => 'Thai', 'flag' => 'th'],
            ['code' => 'vi', 'name' => 'Vietnamese', 'flag' => 'vn'],
            ['code' => 'id', 'name' => 'Indonesian', 'flag' => 'id'],
            ['code' => 'ms', 'name' => 'Malay', 'flag' => 'my'],
            ['code' => 'am', 'name' => 'Amharic', 'flag' => 'et'],
        ];
    }

    /**
     * @return array{code: string, name: string, flag: string}|null
     */
    public static function find(string $code): ?array
    {
        $code = strtolower(trim($code));

        foreach (self::all() as $language) {
            if ($language['code'] === $code) {
                return $language;
            }
        }

        return null;
    }

    public static function flagSvgUrl(string $flagCountryCode): string
    {
        $flag = strtolower(preg_replace('/[^a-z]/i', '', $flagCountryCode) ?: 'un');

        return 'https://flagcdn.com/'.$flag.'.svg';
    }

    /**
     * @return list<array{code: string, name: string, flag: string, flag_url: string}>
     */
    public static function forPicker(): array
    {
        return array_map(static function (array $language): array {
            return [
                ...$language,
                'flag_url' => self::flagSvgUrl($language['flag']),
            ];
        }, self::all());
    }
}
