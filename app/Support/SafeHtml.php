<?php

namespace App\Support;

use DOMDocument;
use DOMElement;
use DOMNode;

/**
 * Allowlisted HTML for journal descriptions, announcement bodies, etc.
 */
final class SafeHtml
{
    /** @var list<string> */
    private const ALLOWED_TAGS = [
        'p', 'br', 'strong', 'b', 'em', 'i', 'u', 'ul', 'ol', 'li',
        'a', 'h2', 'h3', 'blockquote',
    ];

    public static function clean(?string $html): ?string
    {
        if ($html === null) {
            return null;
        }

        $html = trim($html);
        if ($html === '' || $html === '<p><br></p>' || $html === '<p></p>') {
            return null;
        }

        $html = self::decodeEscapedMarkup($html);

        // Plain text without tags: wrap as paragraphs preserving line breaks.
        if ($html === strip_tags($html)) {
            $parts = preg_split("/\R{2,}/", $html) ?: [$html];
            $html = collect($parts)
                ->map(fn (string $block) => '<p>'.nl2br(e(trim($block)), false).'</p>')
                ->implode('');
        }

        $wrapped = '<div id="safe-html-root">'.$html.'</div>';
        $previous = libxml_use_internal_errors(true);
        $dom = new DOMDocument('1.0', 'UTF-8');
        $dom->loadHTML('<?xml encoding="UTF-8">'.$wrapped, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
        libxml_clear_errors();
        libxml_use_internal_errors($previous);

        $root = $dom->getElementById('safe-html-root');
        if (! $root) {
            return null;
        }

        self::sanitizeNode($root);

        $out = '';
        foreach ($root->childNodes as $child) {
            $out .= $dom->saveHTML($child);
        }

        $out = trim($out);
        if ($out === '' || trim(strip_tags($out)) === '') {
            return null;
        }

        return $out;
    }

    public static function isEmpty(?string $html): bool
    {
        return trim(strip_tags((string) $html)) === '';
    }

    /**
     * Render trusted (already cleaned) HTML, or escape plain legacy text.
     */
    public static function display(?string $value): string
    {
        if ($value === null || trim($value) === '') {
            return '';
        }

        $value = self::decodeEscapedMarkup($value);

        if ($value !== strip_tags($value)) {
            return self::clean($value) ?? '';
        }

        return nl2br(e($value), false);
    }

    /**
     * Rich-text pasted as plain text often arrives HTML-entity encoded (&lt;p&gt;…).
     */
    private static function decodeEscapedMarkup(string $html): string
    {
        if (! str_contains($html, '&lt;') || ! preg_match('/&lt;\/?[a-z][^&]*&gt;/i', $html)) {
            return $html;
        }

        return html_entity_decode($html, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    }

    private static function sanitizeNode(DOMNode $node): void
    {
        if (! $node->hasChildNodes()) {
            return;
        }

        /** @var list<DOMNode> $children */
        $children = [];
        foreach ($node->childNodes as $child) {
            $children[] = $child;
        }

        foreach ($children as $child) {
            if ($child->nodeType === XML_TEXT_NODE || $child->nodeType === XML_CDATA_SECTION_NODE) {
                continue;
            }

            if ($child->nodeType !== XML_ELEMENT_NODE || ! ($child instanceof DOMElement)) {
                $node->removeChild($child);

                continue;
            }

            $tag = strtolower($child->tagName);

            if (in_array($tag, ['script', 'style', 'iframe', 'object', 'embed', 'form', 'input', 'button'], true)) {
                $node->removeChild($child);

                continue;
            }

            if (! in_array($tag, self::ALLOWED_TAGS, true)) {
                // Keep children, drop the wrapper.
                while ($child->firstChild) {
                    $node->insertBefore($child->firstChild, $child);
                }
                $node->removeChild($child);

                continue;
            }

            self::sanitizeAttributes($child, $tag);
            self::sanitizeNode($child);
        }
    }

    private static function sanitizeAttributes(DOMElement $el, string $tag): void
    {
        $allowed = $tag === 'a' ? ['href', 'title', 'rel', 'target'] : [];

        /** @var list<string> $names */
        $names = [];
        if ($el->hasAttributes()) {
            foreach ($el->attributes as $attr) {
                $names[] = $attr->name;
            }
        }

        foreach ($names as $name) {
            if (! in_array(strtolower($name), $allowed, true)) {
                $el->removeAttribute($name);
            }
        }

        if ($tag === 'a') {
            $href = trim((string) $el->getAttribute('href'));
            if ($href === '' || preg_match('/^\s*javascript:/i', $href)) {
                $el->removeAttribute('href');
            } elseif (! preg_match('#^(https?:)?//#i', $href) && ! str_starts_with($href, '/') && ! str_starts_with($href, 'mailto:')) {
                $el->setAttribute('href', 'https://'.$href);
            }
            $el->setAttribute('rel', 'noopener noreferrer');
            if ($el->hasAttribute('href')) {
                $el->setAttribute('target', '_blank');
            }
        }
    }
}
