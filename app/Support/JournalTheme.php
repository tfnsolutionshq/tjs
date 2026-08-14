<?php

namespace App\Support;

use App\Models\Journal;

class JournalTheme
{
    public const DEFAULTS = [
        'primary' => '#1b4f72',
        'accent' => '#148f77',
        'nav_bg' => '#0f2f44',
        'nav_text' => '#ffffff',
        'header_bg' => '#1b4f72',
        'header_text' => '#ffffff',
        'page_bg' => '#f7f8fa',
        'surface' => '#ffffff',
        'text' => '#1a2332',
        'muted' => '#5b6b7c',
        'header_size' => 'large', // small|medium|large
        'header_align' => 'left', // left|center
        'font_style' => 'serif', // serif|sans
        'show_subtitle' => true,
        'hero_overlay' => 0.45,
    ];

    public static function for(?Journal $journal): array
    {
        $theme = is_array($journal?->theme) ? $journal->theme : [];

        return array_merge(self::DEFAULTS, array_filter($theme, fn ($v) => $v !== null && $v !== ''));
    }

    public static function cssVariables(?Journal $journal): string
    {
        $t = self::for($journal);
        $height = match ($t['header_size']) {
            'small' => '160px',
            'medium' => '240px',
            default => '340px',
        };

        $pairs = [
            '--j-primary' => $t['primary'],
            '--j-accent' => $t['accent'],
            '--j-nav-bg' => $t['nav_bg'],
            '--j-nav-text' => $t['nav_text'],
            '--j-header-bg' => $t['header_bg'],
            '--j-header-text' => $t['header_text'],
            '--j-page-bg' => $t['page_bg'],
            '--j-surface' => $t['surface'],
            '--j-text' => $t['text'],
            '--j-muted' => $t['muted'],
            '--j-header-height' => $height,
            '--j-hero-overlay' => (string) $t['hero_overlay'],
            '--j-font-display' => $t['font_style'] === 'sans'
                ? "'DM Sans', ui-sans-serif, system-ui, sans-serif"
                : "'Libre Baskerville', Georgia, 'Times New Roman', serif",
            '--j-font-body' => "'DM Sans', ui-sans-serif, system-ui, sans-serif",
        ];

        $css = '';
        foreach ($pairs as $key => $value) {
            $css .= "{$key}: {$value};";
        }

        return $css;
    }

    /** Contrast-friendly text for a hex background (simple luminance). */
    public static function contrastingText(string $hex): string
    {
        $hex = ltrim($hex, '#');
        if (strlen($hex) === 3) {
            $hex = $hex[0].$hex[0].$hex[1].$hex[1].$hex[2].$hex[2];
        }
        if (strlen($hex) !== 6) {
            return '#ffffff';
        }
        $r = hexdec(substr($hex, 0, 2));
        $g = hexdec(substr($hex, 2, 2));
        $b = hexdec(substr($hex, 4, 2));
        $luma = (0.299 * $r + 0.587 * $g + 0.114 * $b) / 255;

        return $luma > 0.62 ? '#0f172a' : '#ffffff';
    }
}
