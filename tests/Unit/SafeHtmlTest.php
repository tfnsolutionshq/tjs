<?php

namespace Tests\Unit;

use App\Support\SafeHtml;
use PHPUnit\Framework\TestCase;

class SafeHtmlTest extends TestCase
{
    public function test_strips_scripts_and_keeps_formatting(): void
    {
        $clean = SafeHtml::clean('Hello <script>alert(1)</script><strong>world</strong>');

        $this->assertStringNotContainsString('<script', (string) $clean);
        $this->assertStringNotContainsString('alert(1)', (string) $clean);
        $this->assertStringContainsString('<strong>world</strong>', (string) $clean);
    }

    public function test_blocks_javascript_links(): void
    {
        $clean = SafeHtml::clean('<a href="javascript:alert(1)">Click</a>');

        $this->assertStringNotContainsString('javascript:', (string) $clean);
    }

    public function test_plain_text_preserves_paragraphs(): void
    {
        $clean = SafeHtml::clean("Line one\n\nLine two");

        $this->assertStringContainsString('<p>', (string) $clean);
        $this->assertStringContainsString('Line one', (string) $clean);
        $this->assertStringContainsString('Line two', (string) $clean);
    }

    public function test_decodes_entity_encoded_markup_before_cleaning(): void
    {
        $encoded = '&lt;p&gt;Hello &lt;strong&gt;world&lt;/strong&gt;&lt;/p&gt;&lt;ul&gt;&lt;li&gt;One&lt;/li&gt;&lt;/ul&gt;';
        $clean = SafeHtml::clean($encoded);

        $this->assertStringContainsString('<strong>world</strong>', (string) $clean);
        $this->assertStringContainsString('<li>One</li>', (string) $clean);
        $this->assertStringNotContainsString('&lt;p&gt;', (string) $clean);
    }

    public function test_display_renders_entity_encoded_markup(): void
    {
        $encoded = '&lt;p&gt;Hello &lt;strong&gt;world&lt;/strong&gt;&lt;/p&gt;';
        $display = SafeHtml::display($encoded);

        $this->assertStringContainsString('<strong>world</strong>', $display);
        $this->assertStringNotContainsString('&lt;strong&gt;', $display);
    }
}
