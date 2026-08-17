<?php

namespace Tests\Unit;

use App\Support\ListLayout;
use Illuminate\Http\Request;
use Tests\TestCase;

class ListLayoutTest extends TestCase
{
    public function test_from_request_defaults_to_grid(): void
    {
        $request = Request::create('/journals', 'GET');

        $this->assertSame(ListLayout::GRID, ListLayout::fromRequest($request));
    }

    public function test_from_request_honors_view_query(): void
    {
        $request = Request::create('/journals', 'GET', ['view' => 'list']);

        $this->assertSame(ListLayout::LIST, ListLayout::fromRequest($request));
    }

    public function test_from_request_uses_custom_default(): void
    {
        $request = Request::create('/browse', 'GET');

        $this->assertSame(ListLayout::LIST, ListLayout::fromRequest($request, ListLayout::LIST));
    }

    public function test_toggle_url_switches_view_and_resets_page(): void
    {
        $request = Request::create('/journals', 'GET', ['view' => 'grid', 'page' => '2']);

        $url = ListLayout::toggleUrl($request, ListLayout::LIST);

        $this->assertStringContainsString('view=list', $url);
        $this->assertStringNotContainsString('page=', $url);
    }
}
