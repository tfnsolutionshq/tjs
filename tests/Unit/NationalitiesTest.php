<?php

namespace Tests\Unit;

use App\Support\Nationalities;
use Tests\TestCase;

class NationalitiesTest extends TestCase
{
    public function test_for_picker_includes_flag_urls(): void
    {
        $picker = Nationalities::forPicker();

        $this->assertNotEmpty($picker);
        $this->assertStringContainsString('flagcdn.com/ng.svg', $picker[array_search('Nigeria', array_column($picker, 'name'), true)]['flag_url']);
    }

    public function test_match_normalizes_common_demonym(): void
    {
        $match = Nationalities::match('Nigerian');

        $this->assertNotNull($match);
        $this->assertSame('Nigeria', $match['name']);
        $this->assertSame('ng', $match['code']);
    }

    public function test_normalize_returns_null_for_blank_values(): void
    {
        $this->assertNull(Nationalities::normalize(''));
        $this->assertNull(Nationalities::normalize(null));
    }
}
