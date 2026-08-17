<?php

namespace App\Support;

use Illuminate\Validation\Rule;

final class AnnouncementType
{
    public const NEWS = 'news';

    public const CALL_FOR_SUBMISSIONS = 'call_for_submissions';

    /**
     * @return list<string>
     */
    public static function all(): array
    {
        return [self::NEWS, self::CALL_FOR_SUBMISSIONS];
    }

    public static function requiredRule(): array
    {
        return ['required', Rule::in(self::all())];
    }

    public static function label(?string $type): string
    {
        return match ($type) {
            self::CALL_FOR_SUBMISSIONS => 'Call for submissions',
            self::NEWS => 'News',
            default => 'Announcement',
        };
    }

    public static function isCallForSubmissions(?string $type): bool
    {
        return $type === self::CALL_FOR_SUBMISSIONS;
    }
}
