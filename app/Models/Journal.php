<?php

namespace App\Models;

use App\Support\JournalTheme;
use App\Support\JournalActivation;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Support\Carbon;

class Journal extends Model
{
    protected $fillable = [
        'slug', 'initials', 'title', 'subtitle', 'description', 'issn', 'eissn', 'publisher',
        'default_license', 'language', 'review_type', 'is_active', 'is_featured', 'featured_requested_at', 'allow_platform_admin_edits',
        'activation_status', 'activation_paid_at', 'activation_expires_at', 'activation_reminders_sent',
        'payment_gateway_preference', 'paystack_public_key', 'paystack_secret_key', 'paystack_split_code',
        'personal_gateway_allowed',
        'doi_mode', 'doi_prefix', 'crossref_username', 'crossref_password',
        'doi_credits_balance', 'doi_credits_lifetime', 'doi_auto_deposit',
        'doi_low_balance_notified_at', 'doi_exhausted_notified_at',
        'membership_price', 'membership_days', 'cover_path',
        'theme', 'logo_path', 'logo_disk', 'header_image_path', 'header_image_disk',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'is_featured' => 'boolean',
            'featured_requested_at' => 'datetime',
            'allow_platform_admin_edits' => 'boolean',
            'personal_gateway_allowed' => 'boolean',
            'doi_auto_deposit' => 'boolean',
            'theme' => 'array',
            'activation_paid_at' => 'datetime',
            'activation_expires_at' => 'datetime',
            'activation_reminders_sent' => 'array',
            'paystack_public_key' => 'encrypted',
            'paystack_secret_key' => 'encrypted',
            'crossref_username' => 'encrypted',
            'crossref_password' => 'encrypted',
            'doi_low_balance_notified_at' => 'datetime',
            'doi_exhausted_notified_at' => 'datetime',
        ];
    }

    /**
     * Decrypt encrypted attributes without throwing when APP_KEY no longer matches stored ciphertext.
     */
    public function fromEncryptedString($value)
    {
        try {
            return parent::fromEncryptedString($value);
        } catch (DecryptException) {
            return null;
        }
    }

    /**
     * Read an encrypted cast without throwing when stored data cannot be decrypted.
     */
    public function readEncrypted(string $key): ?string
    {
        $casts = $this->getCasts();

        if (! isset($casts[$key]) || $casts[$key] !== 'encrypted') {
            $value = $this->getAttribute($key);

            return $value === null ? null : (string) $value;
        }

        $raw = $this->getRawOriginal($key);

        if ($raw === null || $raw === '') {
            return null;
        }

        try {
            $value = $this->getAttribute($key);

            return $value === null ? null : (string) $value;
        } catch (DecryptException) {
            return null;
        }
    }

    public function hasStoredEncrypted(string $key): bool
    {
        $raw = $this->getRawOriginal($key);

        return $raw !== null && $raw !== '';
    }

    public function encryptedAttributeIsCorrupted(string $key): bool
    {
        return (($this->getCasts()[$key] ?? null) === 'encrypted')
            && $this->hasStoredEncrypted($key)
            && $this->readEncrypted($key) === null;
    }

    /**
     * @param  list<string>  $keys
     */
    public function anyEncryptedAttributesCorrupted(array $keys): bool
    {
        foreach ($keys as $key) {
            if ($this->encryptedAttributeIsCorrupted($key)) {
                return true;
            }
        }

        return false;
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
        return $this->publicDiskUrl($this->logo_path, $this->logo_disk);
    }

    public function headerImageUrl(): ?string
    {
        return $this->publicDiskUrl($this->header_image_path, $this->header_image_disk);
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

    private function publicDiskUrl(?string $path, ?string $disk = null): ?string
    {
        if (! $path) {
            return null;
        }
        if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://')) {
            return $path;
        }

        return app(\App\Services\Storage\HybridDisk::class)
            ->url($path, \App\Services\Storage\HybridDisk::KIND_MEDIA, $disk);
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
     * Public catalog: editorially active and activation fee current.
     */
    public function scopeListed(Builder $query): Builder
    {
        return $query
            ->where('is_active', true)
            ->where('activation_status', JournalActivation::STATUS_ACTIVE)
            ->where(function (Builder $q) {
                $q->whereNull('activation_expires_at')
                    ->orWhere('activation_expires_at', '>', now());
            });
    }

    public function isActivationCurrent(): bool
    {
        if ($this->activation_status !== JournalActivation::STATUS_ACTIVE) {
            return false;
        }

        if ($this->activation_expires_at === null) {
            return true;
        }

        return $this->activation_expires_at->isFuture();
    }

    public function isListed(): bool
    {
        return $this->is_active && $this->isActivationCurrent();
    }

    public function managementUnlocked(): bool
    {
        return $this->isActivationCurrent();
    }

    /**
     * Whether this journal still owes an activation payment under current platform policy.
     * When the platform has deactivated activation fees, existing unpaid/expired journals stay locked
     * (they do not auto-unlock), but new journals are waived at create time.
     */
    public function activationNeedsPayment(): bool
    {
        if (! JournalActivation::required()) {
            return false;
        }

        return ! $this->isActivationCurrent();
    }

    /**
     * Locked because activation is unpaid/expired — even if platform fee collection is currently off.
     */
    public function activationLocked(): bool
    {
        return ! $this->isActivationCurrent();
    }

    public function markActivationPaid(?Carbon $from = null): void
    {
        $from ??= now();
        $base = $this->activation_expires_at && $this->activation_expires_at->isFuture()
            ? $this->activation_expires_at->copy()
            : $from->copy();

        $this->forceFill([
            'activation_status' => JournalActivation::STATUS_ACTIVE,
            'activation_paid_at' => $from,
            'activation_expires_at' => $base->addDays(JournalActivation::durationDays()),
            'activation_reminders_sent' => [],
        ])->save();
    }

    public function markActivationExpired(): void
    {
        $this->forceFill([
            'activation_status' => JournalActivation::STATUS_EXPIRED,
        ])->save();
    }

    public function markActivationWaived(): void
    {
        $this->forceFill([
            'activation_status' => JournalActivation::STATUS_ACTIVE,
            'activation_paid_at' => now(),
            'activation_expires_at' => null,
            'activation_reminders_sent' => [],
        ])->save();
    }

    public function daysUntilActivationExpiry(): ?int
    {
        if (! $this->activation_expires_at) {
            return null;
        }

        return (int) now()->startOfDay()->diffInDays($this->activation_expires_at->copy()->startOfDay(), false);
    }

    /**
     * Whether this user may change journal data (settings, volumes, board, etc.).
     * Requires a current activation. Journal team (admin/editor) or allowed platform admins.
     */
    public function userMayMutate(?User $user): bool
    {
        if (! $user) {
            return false;
        }

        if (! $this->managementUnlocked()) {
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

    public function fees(): HasMany
    {
        return $this->hasMany(JournalFee::class);
    }
}
