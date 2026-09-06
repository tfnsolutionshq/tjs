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
            ['code' => 'sv', 'name' => 'Swedish', 'flag' => 'se'],
            ['code' => 'da', 'name' => 'Danish', 'flag' => 'dk'],
            ['code' => 'no', 'name' => 'Norwegian', 'flag' => 'no'],
            ['code' => 'fi', 'name' => 'Finnish', 'flag' => 'fi'],
            ['code' => 'pl', 'name' => 'Polish', 'flag' => 'pl'],
            ['code' => 'cs', 'name' => 'Czech', 'flag' => 'cz'],
            ['code' => 'sk', 'name' => 'Slovak', 'flag' => 'sk'],
            ['code' => 'hu', 'name' => 'Hungarian', 'flag' => 'hu'],
            ['code' => 'ro', 'name' => 'Romanian', 'flag' => 'ro'],
            ['code' => 'bg', 'name' => 'Bulgarian', 'flag' => 'bg'],
            ['code' => 'uk', 'name' => 'Ukrainian', 'flag' => 'ua'],
            ['code' => 'ru', 'name' => 'Russian', 'flag' => 'ru'],
            ['code' => 'el', 'name' => 'Greek', 'flag' => 'gr'],
            ['code' => 'tr', 'name' => 'Turkish', 'flag' => 'tr'],
            ['code' => 'ar', 'name' => 'Arabic', 'flag' => 'sa'],
            ['code' => 'he', 'name' => 'Hebrew', 'flag' => 'il'],
            ['code' => 'fa', 'name' => 'Persian', 'flag' => 'ir'],
            ['code' => 'ur', 'name' => 'Urdu', 'flag' => 'pk'],
            ['code' => 'hi', 'name' => 'Hindi', 'flag' => 'in'],
            ['code' => 'bn', 'name' => 'Bengali', 'flag' => 'bd'],
            ['code' => 'ta', 'name' => 'Tamil', 'flag' => 'in'],
            ['code' => 'te', 'name' => 'Telugu', 'flag' => 'in'],
            ['code' => 'mr', 'name' => 'Marathi', 'flag' => 'in'],
            ['code' => 'gu', 'name' => 'Gujarati', 'flag' => 'in'],
            ['code' => 'zh', 'name' => 'Chinese', 'flag' => 'cn'],
            ['code' => 'ja', 'name' => 'Japanese', 'flag' => 'jp'],
            ['code' => 'ko', 'name' => 'Korean', 'flag' => 'kr'],
            ['code' => 'th', 'name' => 'Thai', 'flag' => 'th'],
            ['code' => 'vi', 'name' => 'Vietnamese', 'flag' => 'vn'],
            ['code' => 'id', 'name' => 'Indonesian', 'flag' => 'id'],
            ['code' => 'ms', 'name' => 'Malay', 'flag' => 'my'],
            ['code' => 'tl', 'name' => 'Filipino', 'flag' => 'ph'],
            ['code' => 'sw', 'name' => 'Swahili', 'flag' => 'tz'],
            ['code' => 'am', 'name' => 'Amharic', 'flag' => 'et'],
            ['code' => 'so', 'name' => 'Somali', 'flag' => 'so'],
            ['code' => 'ha', 'name' => 'Hausa', 'flag' => 'ng'],
            ['code' => 'yo', 'name' => 'Yoruba', 'flag' => 'ng'],
            ['code' => 'ig', 'name' => 'Igbo', 'flag' => 'ng'],
            ['code' => 'ff', 'name' => 'Fulfulde', 'flag' => 'ng'],
            ['code' => 'af', 'name' => 'Afrikaans', 'flag' => 'za'],
            ['code' => 'zu', 'name' => 'Zulu', 'flag' => 'za'],
            ['code' => 'xh', 'name' => 'Xhosa', 'flag' => 'za'],
            ['code' => 'st', 'name' => 'Sesotho', 'flag' => 'ls'],
            ['code' => 'rw', 'name' => 'Kinyarwanda', 'flag' => 'rw'],
            ['code' => 'lg', 'name' => 'Luganda', 'flag' => 'ug'],
            ['code' => 'ak', 'name' => 'Akan', 'flag' => 'gh'],
            ['code' => 'tw', 'name' => 'Twi', 'flag' => 'gh'],
            ['code' => 'pcm', 'name' => 'Nigerian Pidgin', 'flag' => 'ng'],
            ['code' => 'la', 'name' => 'Latin', 'flag' => 'va'],
            ['code' => 'ca', 'name' => 'Catalan', 'flag' => 'es'],
            ['code' => 'eu', 'name' => 'Basque', 'flag' => 'es'],
            ['code' => 'gl', 'name' => 'Galician', 'flag' => 'es'],
            ['code' => 'ga', 'name' => 'Irish', 'flag' => 'ie'],
            ['code' => 'cy', 'name' => 'Welsh', 'flag' => 'gb'],
            ['code' => 'sq', 'name' => 'Albanian', 'flag' => 'al'],
            ['code' => 'sr', 'name' => 'Serbian', 'flag' => 'rs'],
            ['code' => 'hr', 'name' => 'Croatian', 'flag' => 'hr'],
            ['code' => 'bs', 'name' => 'Bosnian', 'flag' => 'ba'],
            ['code' => 'sl', 'name' => 'Slovenian', 'flag' => 'si'],
            ['code' => 'lt', 'name' => 'Lithuanian', 'flag' => 'lt'],
            ['code' => 'lv', 'name' => 'Latvian', 'flag' => 'lv'],
            ['code' => 'et', 'name' => 'Estonian', 'flag' => 'ee'],
            ['code' => 'is', 'name' => 'Icelandic', 'flag' => 'is'],
            ['code' => 'mt', 'name' => 'Maltese', 'flag' => 'mt'],
            ['code' => 'ka', 'name' => 'Georgian', 'flag' => 'ge'],
            ['code' => 'hy', 'name' => 'Armenian', 'flag' => 'am'],
            ['code' => 'az', 'name' => 'Azerbaijani', 'flag' => 'az'],
            ['code' => 'kk', 'name' => 'Kazakh', 'flag' => 'kz'],
            ['code' => 'uz', 'name' => 'Uzbek', 'flag' => 'uz'],
            ['code' => 'mn', 'name' => 'Mongolian', 'flag' => 'mn'],
            ['code' => 'ne', 'name' => 'Nepali', 'flag' => 'np'],
            ['code' => 'si', 'name' => 'Sinhala', 'flag' => 'lk'],
            ['code' => 'my', 'name' => 'Burmese', 'flag' => 'mm'],
            ['code' => 'km', 'name' => 'Khmer', 'flag' => 'kh'],
            ['code' => 'lo', 'name' => 'Lao', 'flag' => 'la'],
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
     * Codes accepted by journal language validation.
     *
     * @return list<string>
     */
    public static function codes(): array
    {
        return array_column(self::all(), 'code');
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
