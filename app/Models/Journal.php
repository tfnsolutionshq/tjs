<?php

namespace App\Models;

use App\Support\JournalTheme;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Support\Facades\Storage;

class Journal extends Model
{
    protected $fillable = [
        'slug', 'initials', 'title', 'subtitle', 'description', 'issn', 'eissn', 'publisher',
        'default_license', 'language', 'review_type', 'is_active', 'is_featured', 'allow_platform_admin_edits',
        'membership_price', 'membership_days', 'cover_path',
        'theme', 'logo_path', 'header_image_path',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'is_featured' => 'boolean',
            'allow_platform_admin_edits' => 'boolean',
            'theme' => 'array',
        ];
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public function themeConfig(): array
    {
        return JournalTheme::for($this);
    }

    public function themeCss(): string
    {
        return JournalTheme::cssVariables($this);
    }

    public function logoUrl(): ?string
    {
        return $this->publicDiskUrl($this->logo_path);
    }

    public function headerImageUrl(): ?string
    {
        return $this->publicDiskUrl($this->header_image_path);
    }

    public static function initialsFromTitle(?string $title): string
    {
        $words = preg_split('/\s+/', trim((string) $title)) ?: [];
        $initials = collect($words)
            ->filter()
            ->take(3)
            ->map(fn (string $word) => strtoupper(substr($word, 0, 1)))
            ->implode('');

        if (strlen($initials) < 2) {
            $initials = strtoupper(substr(preg_replace('/[^A-Za-z0-9]/', '', (string) $title) ?: 'JN', 0, 3));
        }

        return substr($initials, 0, 8);
    }

    public function displayInitials(): string
    {
        $stored = strtoupper(trim((string) ($this->initials ?? '')));

        return $stored !== '' ? $stored : self::initialsFromTitle($this->title);
    }

    private function publicDiskUrl(?string $path): ?string
    {
        if (! $path) {
            return null;
        }
        if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://')) {
            return $path;
        }
        if (Storage::disk('public')->exists($path)) {
            return Storage::disk('public')->url($path);
        }

        return null;
    }

    public function volumes(): HasMany
    {
        return $this->hasMany(Volume::class);
    }

    public function issues(): HasManyThrough
    {
        return $this->hasManyThrough(Issue::class, Volume::class);
    }

    public function articles(): HasMany
    {
        return $this->hasMany(Article::class);
    }

    public function submissions(): HasMany
    {
        return $this->hasMany(Submission::class);
    }

    public function editorialBoard(): HasMany
    {
        return $this->hasMany(EditorialBoardMember::class)->orderBy('sort_order');
    }

    public function reviewerRequests(): HasMany
    {
        return $this->hasMany(JournalReviewerRequest::class);
    }

    public function announcements(): HasMany
    {
        return $this->hasMany(JournalAnnouncement::class);
    }

    public function categories(): HasMany
    {
        return $this->hasMany(Category::class)->orderBy('sort_order')->orderBy('name');
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class)->withPivot('role')->withTimestamps();
    }

    /**
     * Team members ordered by role importance then name.
     *
     * @return \Illuminate\Database\Eloquent\Collection<int, User>
     */
    public function teamMembers()
    {
        $order = array_flip(\App\Support\JournalTeamRoles::all());

        return $this->users()
            ->orderBy('name')
            ->get()
            ->sortBy(fn (User $user) => $order[$user->pivot->role] ?? 99)
            ->values();
    }

    /**
     * Assign (or replace) a single journal role for a user.
     */
    public function assignTeamMember(User $user, string $role): void
    {
        if (! in_array($role, \App\Support\JournalTeamRoles::all(), true)) {
            throw new \InvalidArgumentException("Invalid journal team role [{$role}].");
        }

        // One role per user per journal: clear any prior rows for this pair.
        $this->users()->detach($user->id);
        $this->users()->attach($user->id, ['role' => $role]);

        // Review portal still gates on users.role; elevate members when needed.
        if ($role === \App\Support\JournalTeamRoles::REVIEWER && $user->role === 'member') {
            $user->forceFill(['role' => 'reviewer'])->save();
        }
    }

    public function removeTeamMember(User $user): void
    {
        $this->users()->detach($user->id);
    }

    /**
     * Whether this user may change journal data (settings, volumes, board, etc.).
     * Journal team (admin/editor) always may. Platform admins only when the journal allows it.
     */
    public function userMayMutate(?User $user): bool
    {
        if (! $user) {
            return false;
        }

        if ($user->journals()
            ->where('journals.id', $this->id)
            ->wherePivotIn('role', \App\Support\JournalTeamRoles::manageRoles())
            ->exists()) {
            return true;
        }

        return $user->isAdmin() && $this->allow_platform_admin_edits;
    }

    public function membershipPlans(): HasMany
    {
        return $this->hasMany(MembershipPlan::class);
    }
}
