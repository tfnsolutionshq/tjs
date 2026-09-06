<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Journal;
use App\Models\User;
use App\Services\Journal\CategoryService;
use App\Services\Journal\FeaturedJournalRequestService;
use App\Services\Storage\ArticleStorage;
use App\Services\Storage\HybridDisk;
use App\Support\JournalTheme;
use App\Support\JournalTeamRoles;
use App\Support\Licenses;
use App\Support\ReviewType;
use App\Support\SafeHtml;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

class JournalAdminController extends Controller
{
    public function __construct(
        private ArticleStorage $storage,
        private HybridDisk $disks,
        private FeaturedJournalRequestService $featuredRequests,
    ) {
    }

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
        } elseif ($status === 'featured_requests') {
            $this->featuredRequests->scopePendingRequests($query);
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
            'featured_requests' => $this->featuredRequests->scopePendingRequests(Journal::query())->count(),
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

    public function checkSlug(Request $request): JsonResponse
    {
        $data = $request->validate([
            'slug' => ['required', 'string', 'max:255', 'alpha_dash'],
            'except' => ['nullable', 'integer', 'exists:journals,id'],
        ]);

        $user = $request->user();
        abort_unless($user, 403);

        $exceptId = $data['except'] ?? null;

        if ($exceptId) {
            $journal = Journal::query()->findOrFail($exceptId);
            abort_unless($journal->userMayMutate($user), 403);
        } else {
            abort_unless(
                $user->canAccessPlatformAdmin() || $user->hasVerifiedEmail(),
                403
            );
        }

        $slug = Str::slug($data['slug']);

        if ($slug === '') {
            return response()->json([
                'available' => false,
                'slug' => '',
                'message' => 'Enter a valid slug using letters, numbers, and dashes.',
            ]);
        }

        $taken = Journal::query()
            ->when($exceptId, fn ($q) => $q->where('id', '!=', $exceptId))
            ->where('slug', $slug)
            ->exists();

        return response()->json([
            'available' => ! $taken,
            'slug' => $slug,
            'message' => $taken
                ? 'This slug is already used by another journal. Choose a different one.'
                : 'This slug is available.',
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);
        $data['is_active'] = $request->boolean('is_active', true);
        $data['is_featured'] = $request->boolean('is_featured');
        $data['personal_gateway_allowed'] = $request->boolean('personal_gateway_allowed', true);
        $data['theme'] = $this->themeFromRequest($request);

        $team = $this->validatedInitialAdmin($request);

        $journal = Journal::query()->create($data);
        $this->storeBrandAssets($request, $journal);
        $this->assignInitialAdmin($journal, $team);
        app(CategoryService::class)->seedDefaults($journal);
        $journal->markActivationWaived();

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
        if ($fromJournalManage) {
            unset($data['is_featured']);
            $data['allow_platform_admin_edits'] = $request->boolean('allow_platform_admin_edits');
        } else {
            $data['is_featured'] = $request->boolean('is_featured');
            $data['personal_gateway_allowed'] = $request->boolean('personal_gateway_allowed', true);
        }
        $data['theme'] = $this->themeFromRequest($request);

        unset($data['slug']);

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

        $this->disks->deleteDirectory('journals/'.$slug);

        $journal->delete();

        return redirect()
            ->route('admin.journals.index')
            ->with('status', "Journal “{$title}” deleted.");
    }

    public function approveFeatured(Journal $journal): RedirectResponse
    {
        try {
            $this->featuredRequests->approve($journal);
        } catch (\RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('status', "“{$journal->title}” is now featured on the homepage.");
    }

    public function dismissFeatured(Journal $journal): RedirectResponse
    {
        $this->featuredRequests->dismiss($journal);

        return back()->with('status', 'Featured request dismissed.');
    }

    /**
     * @return array<string, mixed>
     */
    private function validated(Request $request, ?Journal $journal = null): array
    {
        if ($request->filled('slug')) {
            $request->merge(['slug' => Str::slug($request->string('slug')->trim())]);
        }

        if ($request->filled('initials')) {
            $clean = strtoupper(preg_replace('/[^A-Za-z0-9]/', '', $request->string('initials')->trim()) ?? '');
            $request->merge(['initials' => $clean !== '' ? $clean : null]);
        } else {
            $request->merge(['initials' => null]);
        }

        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'initials' => ['nullable', 'string', 'max:8', 'regex:/^[A-Z0-9]+$/'],
            'subtitle' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'issn' => ['nullable', 'string', 'max:32'],
            'eissn' => ['nullable', 'string', 'max:32'],
            'publisher' => ['nullable', 'string', 'max:255'],
            'default_license' => ['nullable', 'string', Licenses::rule()],
            'language' => ['nullable', 'string', 'max:16'],
            'review_type' => ReviewType::requiredRule(),
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
        ], [
            'initials.max' => 'Initials may be at most 8 characters.',
            'initials.regex' => 'Use only letters and numbers in the initials.',
        ]);

        if ($journal === null) {
            $slugRules = [
                'slug' => [
                    'required',
                    'string',
                    'max:255',
                    'alpha_dash',
                    Rule::unique('journals', 'slug'),
                ],
            ];

            $data = array_merge($data, $request->validate($slugRules, [
                'slug.unique' => 'This slug is already used by another journal. Choose a different one.',
                'slug.alpha_dash' => 'Use only letters, numbers, and dashes in the slug.',
                'slug.required' => 'A URL slug is required.',
            ]));
        }

        if (($data['initials'] ?? null) === '') {
            $data['initials'] = null;
        }

        $data['description'] = SafeHtml::clean($data['description'] ?? null);

        return $data;
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
        if ($request->boolean('remove_logo') && $journal->logo_path) {
            $this->disks->delete($journal->logo_path, HybridDisk::KIND_MEDIA, $journal->logo_disk);
            $journal->logo_path = null;
            $journal->logo_disk = null;
        }
        if ($request->boolean('remove_header_image') && $journal->header_image_path) {
            $this->disks->delete($journal->header_image_path, HybridDisk::KIND_MEDIA, $journal->header_image_disk);
            $journal->header_image_path = null;
            $journal->header_image_disk = null;
        }

        if ($request->hasFile('logo')) {
            if ($journal->logo_path) {
                $this->disks->delete($journal->logo_path, HybridDisk::KIND_MEDIA, $journal->logo_disk);
            }
            [$journal->logo_path, $journal->logo_disk] = $this->storage->storeBrandAsset($journal, $request->file('logo'), 'logo');
        }
        if ($request->hasFile('header_image')) {
            if ($journal->header_image_path) {
                $this->disks->delete($journal->header_image_path, HybridDisk::KIND_MEDIA, $journal->header_image_disk);
            }
            [$journal->header_image_path, $journal->header_image_disk] = $this->storage->storeBrandAsset($journal, $request->file('header_image'), 'header');
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
            $user->sendEmailVerificationNotification();
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
