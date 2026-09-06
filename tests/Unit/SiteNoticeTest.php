<?php

namespace Tests\Unit;

use App\Models\Setting;
use App\Support\SiteNotice;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SiteNoticeTest extends TestCase
{
    use RefreshDatabase;

    public function test_active_notice_returns_null_when_disabled(): void
    {
        Setting::putMany([
            'site_notice_enabled' => '0',
            'site_notice_message' => 'Hello',
            'site_notice_set_at' => now()->toIso8601String(),
        ]);

        $this->assertNull(SiteNotice::active());
    }

    public function test_active_notice_expires_after_one_week(): void
    {
        Setting::putMany([
            'site_notice_enabled' => '1',
            'site_notice_message' => 'Testing only',
            'site_notice_style' => 'warning',
            'site_notice_set_at' => now()->subDays(8)->toIso8601String(),
        ]);

        $this->assertNull(SiteNotice::active());
        $this->assertSame('0', Setting::getValue('site_notice_enabled'));
        $this->assertSame('', Setting::getValue('site_notice_message'));
    }

    public function test_save_stamps_set_at_when_message_changes(): void
    {
        Setting::putMany([
            'site_notice_enabled' => '1',
            'site_notice_message' => 'Old message',
            'site_notice_set_at' => now()->subDay()->toIso8601String(),
        ]);

        SiteNotice::save(true, 'New testing notice', 'info');

        $this->assertSame('New testing notice', Setting::getValue('site_notice_message'));
        $this->assertNotSame('', Setting::getValue('site_notice_set_at'));
        $this->assertNotNull(SiteNotice::active());
    }
}
