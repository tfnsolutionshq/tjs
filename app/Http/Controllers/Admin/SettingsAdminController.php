<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Setting;
use App\Support\Licenses;
use App\Support\PlatformSettings;
use App\Support\SiteNotice;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class SettingsAdminController extends Controller
{
    public function index(Request $request): View
    {
        $tab = $request->string('tab')->toString();
        if (! in_array($tab, ['general', 'categories'], true)) {
            $tab = 'general';
        }

        $settings = PlatformSettings::current();
        $siteNotice = SiteNotice::adminFormState();
        $siteNoticeHint = SiteNotice::adminHint($siteNotice['set_at']);
        $categories = Category::query()
            ->withCount('articles')
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        return view('admin.settings.index', compact('tab', 'settings', 'categories', 'siteNotice', 'siteNoticeHint'));
    }

    public function updateGeneral(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:64'],
            'full_name' => ['required', 'string', 'max:160'],
            'organization' => ['required', 'string', 'max:160'],
            'publisher' => ['required', 'string', 'max:160'],
            'default_license' => ['required', 'string', Licenses::rule()],
            'default_language' => ['required', 'string', 'max:16'],
            'currency' => ['required', 'string', Rule::in(['NGN', 'USD', 'EUR', 'GBP'])],
            'membership_platform_enabled' => ['sometimes', 'boolean'],
            'membership_platform_price' => ['required', 'integer', 'min:0'],
            'membership_platform_days' => ['required', 'integer', 'min:1', 'max:3650'],
            'journal_activation_enabled' => ['sometimes', 'boolean'],
            'journal_activation_price' => ['required', 'integer', 'min:0'],
            'journal_activation_days' => ['required', 'integer', 'min:1', 'max:3650'],
            'payments_split_fee_percent' => ['required', 'integer', 'min:0', 'max:100'],
            'doi_enabled' => ['sometimes', 'boolean'],
            'doi_usd_to_ngn' => ['required', 'integer', 'min:1', 'max:1000000'],
            'doi_credit_price_usd' => ['required', 'numeric', 'min:0'],
            'doi_threshold_absolute' => ['required', 'integer', 'min:0', 'max:100000'],
            'doi_threshold_percent' => ['required', 'integer', 'min:0', 'max:100'],
            'doi_platform_prefix' => ['required', 'string', 'max:120'],
            'site_notice_enabled' => ['sometimes', 'boolean'],
            'site_notice_message' => ['nullable', 'string', 'max:500'],
            'site_notice_style' => ['nullable', 'string', Rule::in(['info', 'warning', 'success'])],
        ]);

        $data['membership_platform_enabled'] = $request->boolean('membership_platform_enabled');
        $data['journal_activation_enabled'] = $request->boolean('journal_activation_enabled');
        $data['doi_enabled'] = $request->boolean('doi_enabled');
        $data['currency'] = strtoupper($data['currency']);
        $data['payments_split_fee_percent'] = max(0, min(100, (int) $data['payments_split_fee_percent']));
        $data['doi_usd_to_ngn'] = max(1, (int) $data['doi_usd_to_ngn']);
        $data['doi_credit_price_usd'] = max(0, (float) $data['doi_credit_price_usd']);
        $data['doi_threshold_absolute'] = max(0, (int) $data['doi_threshold_absolute']);
        $data['doi_threshold_percent'] = max(0, min(100, (int) $data['doi_threshold_percent']));
        $data['doi_platform_prefix'] = rtrim((string) $data['doi_platform_prefix'], '/');

        $noticeEnabled = $request->boolean('site_notice_enabled');
        $noticeMessage = (string) $request->input('site_notice_message', '');
        $noticeStyle = (string) $request->input('site_notice_style', 'info');
        unset($data['site_notice_enabled'], $data['site_notice_message'], $data['site_notice_style']);

        Setting::putMany($data);
        SiteNotice::save($noticeEnabled, $noticeMessage, $noticeStyle);
        PlatformSettings::applyToConfig();

        return redirect()
            ->route('admin.settings.index', ['tab' => 'general'])
            ->with('status', 'General settings saved.');
    }

    public function storeCategory(Request $request): RedirectResponse
    {
        return redirect()
            ->route('admin.settings.index', ['tab' => 'categories'])
            ->withErrors(['category' => 'Categories are managed per journal. Open the journal settings in Journal Manage.']);
    }

    public function updateCategory(Request $request, Category $category): RedirectResponse
    {
        return redirect()
            ->route('admin.settings.index', ['tab' => 'categories'])
            ->withErrors(['category' => 'Categories are managed per journal. Open the journal settings in Journal Manage.']);
    }

    public function destroyCategory(Category $category): RedirectResponse
    {
        return redirect()
            ->route('admin.settings.index', ['tab' => 'categories'])
            ->withErrors(['category' => 'Categories are managed per journal. Open the journal settings in Journal Manage.']);
    }
}
