<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Setting;
use App\Support\Licenses;
use App\Support\PlatformSettings;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
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
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120', 'unique:categories,name'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $slug = Str::slug($data['name']);
        if (Category::query()->where('slug', $slug)->exists()) {
            return back()->withErrors(['name' => 'A category with a similar name already exists.'])->withInput();
        }

        Category::query()->create([
            'name' => trim($data['name']),
            'slug' => $slug,
            'is_active' => $request->boolean('is_active', true),
            'sort_order' => ((int) Category::query()->max('sort_order')) + 1,
        ]);

        return redirect()
            ->route('admin.settings.index', ['tab' => 'categories'])
            ->with('status', 'Category created.');
    }

    public function updateCategory(Request $request, Category $category): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120', Rule::unique('categories', 'name')->ignore($category->id)],
            'is_active' => ['nullable', 'boolean'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:9999'],
        ]);

        $slug = Str::slug($data['name']);
        if (
            Category::query()
                ->where('slug', $slug)
                ->where('id', '!=', $category->id)
                ->exists()
        ) {
            return back()->withErrors(['name' => 'A category with a similar name already exists.'])->withInput();
        }

        $category->update([
            'name' => trim($data['name']),
            'slug' => $slug,
            'is_active' => $request->boolean('is_active'),
            'sort_order' => $data['sort_order'] ?? $category->sort_order,
        ]);

        return redirect()
            ->route('admin.settings.index', ['tab' => 'categories'])
            ->with('status', 'Category updated.');
    }

    public function destroyCategory(Category $category): RedirectResponse
    {
        if ($category->articles()->exists()) {
            return redirect()
                ->route('admin.settings.index', ['tab' => 'categories'])
                ->withErrors(['category' => 'Cannot delete “'.$category->name.'” while articles still use it. Deactivate it instead.']);
        }

        $category->delete();

        return redirect()
            ->route('admin.settings.index', ['tab' => 'categories'])
            ->with('status', 'Category deleted.');
    }
}
