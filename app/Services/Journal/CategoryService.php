<?php

namespace App\Services\Journal;

use App\Models\Category;
use App\Models\Journal;
use Illuminate\Support\Str;

class CategoryService
{
    /**
     * @var list<string>
     */
    public const DEFAULTS = [
        'Research Article',
        'Review',
        'Editorial',
        'Case Study',
        'Commentary',
        'Short Communication',
        'Letter',
        'Perspective',
    ];

    public function seedDefaults(Journal $journal): void
    {
        foreach (self::DEFAULTS as $i => $name) {
            Category::query()->firstOrCreate(
                [
                    'journal_id' => $journal->id,
                    'slug' => Str::slug($name),
                ],
                [
                    'name' => $name,
                    'is_active' => true,
                    'sort_order' => $i + 1,
                ]
            );
        }
    }
}
