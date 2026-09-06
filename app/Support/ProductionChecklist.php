<?php

namespace App\Support;

final class ProductionChecklist
{
    /**
     * @return list<string>
     */
    public static function keys(): array
    {
        return [
            'title_verified',
            'authors_verified',
            'affiliations_verified',
            'abstract_verified',
            'keywords_verified',
            'formatting_applied',
            'branding_applied',
            'headings_formatted',
            'tables_checked',
            'figures_checked',
            'references_checked',
            'layout_checked',
            'document_uploaded',
        ];
    }

    /**
     * @return array<string, string>
     */
    public static function labels(): array
    {
        return [
            'title_verified' => 'Title verified',
            'authors_verified' => 'Author names verified',
            'affiliations_verified' => 'Affiliations verified',
            'abstract_verified' => 'Abstract verified',
            'keywords_verified' => 'Keywords verified',
            'formatting_applied' => 'Journal formatting applied',
            'branding_applied' => 'Journal branding applied',
            'headings_formatted' => 'Headings formatted',
            'tables_checked' => 'Tables checked',
            'figures_checked' => 'Figures checked',
            'references_checked' => 'References checked',
            'layout_checked' => 'Page layout checked',
            'document_uploaded' => 'Final document uploaded',
        ];
    }

    /**
     * @param  array<string, mixed>|null  $values
     * @return array<string, bool>
     */
    public static function normalize(?array $values): array
    {
        $normalized = [];
        foreach (self::keys() as $key) {
            $normalized[$key] = (bool) ($values[$key] ?? false);
        }

        return $normalized;
    }
}
