<?php

namespace App\Support;

final class EditorialBoardRoles
{
    /**
     * Common editorial board role titles for the settings dropdown.
     *
     * @return list<string>
     */
    public static function options(): array
    {
        return [
            'Editor-in-Chief',
            'Deputy Editor-in-Chief',
            'Managing Editor',
            'Associate Editor',
            'Section Editor',
            'Guest Editor',
            'Editorial Board Member',
            'Advisory Board Member',
            'Copy Editor',
            'Production Editor',
        ];
    }
}
