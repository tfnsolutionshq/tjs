<?php

namespace App\Http\Controllers\JournalManage;

use App\Http\Controllers\Admin\JournalAdminController as PlatformJournalAdminController;
use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Journal;
use App\Services\Journal\CategoryService;
use App\Services\Journal\FeaturedJournalRequestService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class SettingsController extends Controller
{
    public function __construct(
        private PlatformJournalAdminController $platform,
        private CategoryService $categories,
        private FeaturedJournalRequestService $featuredRequests,
    ) {
    }

    public function edit(Journal $journal): View
    {
        $journal->load(['editorialBoard']);

        if ($journal->categories()->count() === 0) {
            $this->categories->seedDefaults($journal);
        }

        $categories = $journal->categories()
            ->withCount('articles')
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        return view('journal-manage.settings', [
            'journal' => $journal,
            'manageJournal' => $journal,
            'theme' => $journal->themeConfig(),
            'board' => $journal->editorialBoard,
            'canMutate' => $journal->userMayMutate(auth()->user()),
            'categories' => $categories,
        ]);
    }

    public function update(Request $request, Journal $journal): RedirectResponse
    {
        $this->platform->persistProfile($request, $journal, fromJournalManage: true);

        return redirect()
            ->route('journal.manage.settings.edit', $journal)
            ->with('status', 'Journal settings saved.');
    }

    public function requestFeatured(Request $request, Journal $journal): RedirectResponse
    {
        abort_unless($journal->userMayMutate($request->user()), 403);

        try {
            $this->featuredRequests->request($journal, $request->user());
        } catch (\RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('status', 'Featured request sent to the platform administrators.');
    }

    public function storeCategory(Request $request, Journal $journal): RedirectResponse
    {
        abort_unless($journal->userMayMutate($request->user()), 403);

        $data = $request->validate([
            'name' => [
                'required',
                'string',
                'max:120',
                Rule::unique('categories', 'name')->where(fn ($q) => $q->where('journal_id', $journal->id)),
            ],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $slug = Str::slug($data['name']);
        if (Category::query()->forJournal($journal)->where('slug', $slug)->exists()) {
            return back()->withErrors(['name' => 'A category with a similar name already exists for this journal.'])->withInput();
        }

        Category::query()->create([
            'journal_id' => $journal->id,
            'name' => trim($data['name']),
            'slug' => $slug,
            'is_active' => $request->boolean('is_active', true),
            'sort_order' => ((int) Category::query()->forJournal($journal)->max('sort_order')) + 1,
        ]);

        return redirect()
            ->route('journal.manage.settings.edit', $journal)
            ->with('status', 'Category created.');
    }

    public function updateCategory(Request $request, Journal $journal, Category $category): RedirectResponse
    {
        abort_unless($journal->userMayMutate($request->user()), 403);
        abort_unless((int) $category->journal_id === (int) $journal->id, 404);

        $data = $request->validate([
            'name' => [
                'required',
                'string',
                'max:120',
                Rule::unique('categories', 'name')
                    ->where(fn ($q) => $q->where('journal_id', $journal->id))
                    ->ignore($category->id),
            ],
            'is_active' => ['nullable', 'boolean'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:9999'],
        ]);

        $slug = Str::slug($data['name']);
        if (
            Category::query()
                ->forJournal($journal)
                ->where('slug', $slug)
                ->where('id', '!=', $category->id)
                ->exists()
        ) {
            return back()->withErrors(['name' => 'A category with a similar name already exists for this journal.'])->withInput();
        }

        $category->update([
            'name' => trim($data['name']),
            'slug' => $slug,
            'is_active' => $request->boolean('is_active'),
            'sort_order' => $data['sort_order'] ?? $category->sort_order,
        ]);

        return redirect()
            ->route('journal.manage.settings.edit', $journal)
            ->with('status', 'Category updated.');
    }

    public function destroyCategory(Request $request, Journal $journal, Category $category): RedirectResponse
    {
        abort_unless($journal->userMayMutate($request->user()), 403);
        abort_unless((int) $category->journal_id === (int) $journal->id, 404);

        if ($category->articles()->exists()) {
            return redirect()
                ->route('journal.manage.settings.edit', $journal)
                ->withErrors(['category' => 'Cannot delete “'.$category->name.'” while articles still use it. Deactivate it instead.']);
        }

        $category->delete();

        return redirect()
            ->route('journal.manage.settings.edit', $journal)
            ->with('status', 'Category deleted.');
    }
}
