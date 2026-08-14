<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Journal;
use App\Models\User;
use App\Support\JournalTheme;
use App\Support\JournalTeamRoles;
use App\Support\Licenses;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

class JournalAdminController extends Controller
{
    public function index(Request $request): View
    {
        $query = Journal::query()
            ->withCount(['articles', 'volumes', 'submissions'])
            ->orderByDesc('is_featured')
            ->orderByDesc('updated_at')
            ->orderBy('title');

        if ($search = trim((string) $request->get('q'))) {
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                    ->orWhere('slug', 'like', "%{$search}%")
                    ->orWhere('issn', 'like', "%{$search}%")
                    ->orWhere('eissn', 'like', "%{$search}%");
            });
        }

        $status = $request->get('status');
        if ($status === 'active') {
            $query->where('is_active', true);
        } elseif ($status === 'inactive') {
            $query->where('is_active', false);
        } elseif ($status === 'featured') {
            $query->where('is_featured', true);
        }

        $perPage = (int) $request->integer('per_page', 6);
        if (! in_array($perPage, [6, 12, 24], true)) {
            $perPage = 6;
        }

        $journals = $query->paginate($perPage)->withQueryString();

        $stats = [
            'total' => Journal::query()->count(),
            'active' => Journal::query()->where('is_active', true)->count(),
            'featured' => Journal::query()->where('is_featured', true)->count(),
            'inactive' => Journal::query()->where('is_active', false)->count(),
        ];

        return view('admin.journals.index', compact('journals', 'stats', 'perPage'));
    }

    public function create(): View
    {
        return view('admin.journals.create', [
            'journal' => new Journal,
            'theme' => JournalTheme::DEFAULTS,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);
        $data['is_active'] = $request->boolean('is_active', true);
        $data['is_featured'] = $request->boolean('is_featured');
        $data['theme'] = $this->themeFromRequest($request);

        $team = $this->validatedInitialAdmin($request);

        $journal = Journal::query()->create($data);
        $this->storeBrandAssets($request, $journal);
        $this->assignInitialAdmin($journal, $team);

        return redirect()
            ->route('admin.journals.edit', $journal)
            ->with('status', 'Journal created. Assign more team members below if needed.');
    }

    public function edit(Journal $journal): View
    {
        $journal->load([
            'users' => fn ($q) => $q->orderBy('name'),
            'editorialBoard',
        ]);

        return view('admin.journals.edit', [
            'journal' => $journal,
            'theme' => $journal->themeConfig(),
            'team' => $journal->teamMembers(),
            'platformEditsLocked' => ! $journal->allow_platform_admin_edits,
        ]);
    }

    public function update(Request $request, Journal $journal): RedirectResponse
    {
        $this->persistProfile($request, $journal, fromJournalManage: false);

        return redirect()
            ->route('admin.journals.edit', $journal)
            ->with('status', 'Journal updated.');
    }

    /**
     * Shared create/update persistence for platform admin and journal-manage settings.
     */
    public function persistProfile(Request $request, Journal $journal, bool $fromJournalManage = false): void
    {
        abort_unless(
            $journal->userMayMutate($request->user()),
            403,
            'This journal has disabled edits by platform administrators.'
        );

        $data = $this->validated($request, $journal);
        $data['is_active'] = $request->boolean('is_active');
        $data['is_featured'] = $request->boolean('is_featured');
        $data['theme'] = $this->themeFromRequest($request);

        if ($fromJournalManage) {
            $data['allow_platform_admin_edits'] = $request->boolean('allow_platform_admin_edits');
        }

        $journal->update($data);
        $this->storeBrandAssets($request, $journal);
    }

    public function destroy(Request $request, Journal $journal): RedirectResponse
    {
        abort_unless(
            $journal->userMayMutate($request->user()),
            403,
            'This journal has disabled edits by platform administrators. Re-enable platform edits in journal settings before deleting, or ask a journal admin.'
        );

        $request->validate([
            'confirm' => ['required', 'string', Rule::in([$journal->slug])],
        ], [
            'confirm.in' => 'Type the journal slug exactly to confirm deletion.',
            'confirm.required' => 'Type the journal slug to confirm deletion.',
        ]);

        $title = $journal->title;
        $slug = $journal->slug;

        Storage::disk('public')->deleteDirectory('journals/'.$slug);

        $journal->delete();

        return redirect()
            ->route('admin.journals.index')
            ->with('status', "Journal “{$title}” deleted.");
    }

    /**
     * @return array<string, mixed>
     */
    private function validated(Request $request, ?Journal $journal = null): array
    {
        return $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'slug' => [
                'required',
                'string',
                'max:255',
                'alpha_dash',
                Rule::unique('journals', 'slug')->ignore($journal?->id),
            ],
            'subtitle' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'issn' => ['nullable', 'string', 'max:32'],
            'eissn' => ['nullable', 'string', 'max:32'],
            'publisher' => ['nullable', 'string', 'max:255'],
            'default_license' => ['nullable', 'string', Licenses::rule()],
            'language' => ['nullable', 'string', 'max:16'],
            'membership_price' => ['nullable', 'integer', 'min:0'],
            'membership_days' => ['nullable', 'integer', 'min:1'],
            'cover_path' => ['nullable', 'string', 'max:255'],
            'theme.primary' => ['nullable', 'string', 'max:20'],
            'theme.accent' => ['nullable', 'string', 'max:20'],
            'theme.nav_bg' => ['nullable', 'string', 'max:20'],
            'theme.nav_text' => ['nullable', 'string', 'max:20'],
            'theme.header_bg' => ['nullable', 'string', 'max:20'],
            'theme.header_text' => ['nullable', 'string', 'max:20'],
            'theme.page_bg' => ['nullable', 'string', 'max:20'],
            'theme.surface' => ['nullable', 'string', 'max:20'],
            'theme.text' => ['nullable', 'string', 'max:20'],
            'theme.muted' => ['nullable', 'string', 'max:20'],
            'theme.header_size' => ['nullable', Rule::in(['small', 'medium', 'large'])],
            'theme.header_align' => ['nullable', Rule::in(['left', 'center'])],
            'theme.font_style' => ['nullable', Rule::in(['serif', 'sans'])],
            'theme.hero_overlay' => ['nullable', 'numeric', 'min:0', 'max:0.9'],
            'theme.show_subtitle' => ['nullable'],
            'logo' => ['nullable', 'image', 'max:10240'],
            'header_image' => ['nullable', 'image', 'max:15360'],
            'remove_logo' => ['nullable', 'boolean'],
            'remove_header_image' => ['nullable', 'boolean'],
        ]);
    }

    private function themeFromRequest(Request $request): array
    {
        $input = (array) $request->input('theme', []);
        $theme = array_merge(JournalTheme::DEFAULTS, array_intersect_key($input, JournalTheme::DEFAULTS));
        $theme['show_subtitle'] = $request->boolean('theme.show_subtitle');
        $theme['hero_overlay'] = isset($input['hero_overlay']) ? (float) $input['hero_overlay'] : JournalTheme::DEFAULTS['hero_overlay'];

        // Auto-contrast nav/header text if user didn't override explicitly beyond defaults handling
        if (! empty($theme['nav_bg']) && empty($request->input('theme.nav_text'))) {
            $theme['nav_text'] = JournalTheme::contrastingText($theme['nav_bg']);
        }
        if (! empty($theme['header_bg']) && empty($request->input('theme.header_text'))) {
            $theme['header_text'] = JournalTheme::contrastingText($theme['header_bg']);
        }

        return $theme;
    }

    private function storeBrandAssets(Request $request, Journal $journal): void
    {
        $dir = 'journals/'.$journal->slug.'/branding';

        if ($request->boolean('remove_logo') && $journal->logo_path) {
            Storage::disk('public')->delete($journal->logo_path);
            $journal->logo_path = null;
        }
        if ($request->boolean('remove_header_image') && $journal->header_image_path) {
            Storage::disk('public')->delete($journal->header_image_path);
            $journal->header_image_path = null;
        }

        if ($request->hasFile('logo')) {
            if ($journal->logo_path) {
                Storage::disk('public')->delete($journal->logo_path);
            }
            $journal->logo_path = $request->file('logo')->store($dir, 'public');
        }
        if ($request->hasFile('header_image')) {
            if ($journal->header_image_path) {
                Storage::disk('public')->delete($journal->header_image_path);
            }
            $journal->header_image_path = $request->file('header_image')->store($dir, 'public');
        }

        $journal->save();
    }

    /**
     * Optional journal admin assigned at create time.
     *
     * @return array<string, mixed>|null
     */
    private function validatedInitialAdmin(Request $request): ?array
    {
        if (! $request->boolean('assign_admin')) {
            return null;
        }

        $mode = $request->input('admin_mode', 'existing');

        if ($mode === 'create') {
            return $request->validate([
                'admin_name' => ['required', 'string', 'max:255'],
                'admin_email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
                'admin_password' => ['required', 'confirmed', Password::defaults()],
            ]);
        }

        return $request->validate([
            'admin_email' => ['required', 'string', 'email', 'max:255', 'exists:users,email'],
        ], [
            'admin_email.exists' => 'No user found with that email. Switch to “Create new account”.',
        ]);
    }

    /**
     * @param  array<string, mixed>|null  $team
     */
    private function assignInitialAdmin(Journal $journal, ?array $team): void
    {
        if ($team === null) {
            return;
        }

        if (isset($team['admin_name'], $team['admin_password'])) {
            $user = User::query()->create([
                'name' => $team['admin_name'],
                'email' => $team['admin_email'],
                'password' => Hash::make($team['admin_password']),
                'role' => 'member',
            ]);
        } else {
            $user = User::query()->where('email', $team['admin_email'])->first();
            if (! $user) {
                // Should not happen after validation for create-mode; soft-fail for existing.
                return;
            }
        }

        $journal->assignTeamMember($user, JournalTeamRoles::ADMIN);
    }
}
