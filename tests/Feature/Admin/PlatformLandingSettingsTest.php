<?php

namespace Tests\Feature\Admin;

use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PlatformLandingSettingsTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_update_landing_page_copy(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)
            ->put(route('admin.settings.general'), [
                'name' => 'TJS',
                'full_name' => 'TurboFlux Journal System',
                'organization' => 'Turbo Flux Network Solutions',
                'publisher' => 'Turbo Flux Network Solutions',
                'landing_headline' => 'Custom hero headline.',
                'pitch' => 'Custom pitch shown in hero and footer.',
                'landing_about_heading' => 'Custom about heading.',
                'description' => "First paragraph.\n\nSecond paragraph.",
                'landing_footer_publishing' => 'Custom publishing footer blurb.',
                'default_license' => 'CC BY 4.0',
                'default_language' => 'en',
                'currency' => 'NGN',
                'membership_platform_enabled' => '1',
                'membership_platform_price' => 15000,
                'membership_platform_days' => 365,
                'journal_activation_enabled' => '1',
                'journal_activation_price' => 50000,
                'journal_activation_days' => 365,
                'payments_split_fee_percent' => 0,
                'doi_enabled' => '1',
                'doi_usd_to_ngn' => 2000,
                'doi_credit_price_usd' => 1,
                'doi_threshold_absolute' => 20,
                'doi_threshold_percent' => 10,
                'doi_platform_prefix' => '10.0000/tjs',
            ])
            ->assertRedirect(route('admin.settings.index', ['tab' => 'general']));

        $this->get(route('home'))
            ->assertOk()
            ->assertSee('Custom hero headline.', false)
            ->assertSee('Custom pitch shown in hero and footer.', false)
            ->assertSee('Custom about heading.', false)
            ->assertSee('First paragraph.', false)
            ->assertSee('Second paragraph.', false)
            ->assertSee('Custom publishing footer blurb.', false);

        $this->assertSame(
            'Custom pitch shown in hero and footer.',
            Setting::getValue('pitch')
        );
    }
}
