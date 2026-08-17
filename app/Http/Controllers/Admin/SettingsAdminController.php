<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Setting;
use App\Support\Licenses;
use App\Support\PlatformSettings;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
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
        $categories = Category::query()
            ->withCount('articles')
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        return view('admin.settings.index', compact('tab', 'settings', 'categories'));
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
            'currency' => ['required', 'string', 'max:8'],
            'membership_platform_price' => ['required', 'integer', 'min:0'],
            'membership_platform_days' => ['required', 'integer', 'min:1', 'max:3650'],
        ]);

        Setting::putMany($data);
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
