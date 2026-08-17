<?php

namespace Tests\Unit;

use App\Support\FormHelp;
use Tests\TestCase;

class FormHelpTest extends TestCase
{
    public function test_get_returns_help_text_for_known_field(): void
    {
        $this->assertSame(
            'Full article title as it should appear on the public site and in citations.',
            FormHelp::get('article.title')
        );
    }

    public function test_get_returns_null_for_unknown_field(): void
    {
        $this->assertNull(FormHelp::get('missing.field'));
    }

    public function test_has_reports_known_keys(): void
    {
        $this->assertTrue(FormHelp::has('auth.email'));
        $this->assertFalse(FormHelp::has('auth.missing'));
    }
}
