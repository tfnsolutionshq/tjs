<?php

namespace App\Support;

class JournalTeamRoles
{
    public const ADMIN = 'admin';

    public const EDITOR = 'editor';

    public const REVIEWER = 'reviewer';

    /**
     * Roles that can open /j/{slug}/manage.
     *
     * @return list<string>
     */
    public static function manageRoles(): array
    {
        return [self::ADMIN, self::EDITOR];
    }

    /**
     * All assignable journal team roles.
     *
     * @return list<string>
     */
    public static function all(): array
    {
        return [self::ADMIN, self::EDITOR, self::REVIEWER];
    }

    public static function label(string $role): string
    {
        return match ($role) {
            self::ADMIN => 'Journal admin',
            self::EDITOR => 'Editor',
            self::REVIEWER => 'Reviewer',
            default => ucfirst($role),
        };
    }

    public static function description(string $role): string
    {
        return match ($role) {
            self::ADMIN => 'Full control of this journal’s manage portal.',
            self::EDITOR => 'Manage articles, submissions, and plans for this journal.',
            self::REVIEWER => 'Review assigned submissions for this journal.',
            default => '',
        };
    }
}
