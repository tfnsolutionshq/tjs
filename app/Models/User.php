<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'affiliation',
        'orcid',
        'bio',
        'position',
        'is_public_reviewer',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_public_reviewer' => 'boolean',
        ];
    }

    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    public function isEditor(): bool
    {
        return in_array($this->role, ['admin', 'editor'], true);
    }

    public function isReviewer(): bool
    {
        return in_array($this->role, ['admin', 'editor', 'reviewer'], true);
    }

    /**
     * True when the user may use the member review queue (global reviewer role,
     * journal-team reviewer pivot, or existing assignments).
     */
    public function canAccessReviewQueue(): bool
    {
        if ($this->isReviewer()) {
            return true;
        }

        if ($this->journals()->wherePivot('role', \App\Support\JournalTeamRoles::REVIEWER)->exists()) {
            return true;
        }

        return $this->hasMany(ReviewerAssignment::class, 'reviewer_id')->exists();
    }

    /**
     * Pivot role for a specific journal, if any.
     */
    public function journalTeamRole(Journal $journal): ?string
    {
        if ($this->isAdmin()) {
            return \App\Support\JournalTeamRoles::ADMIN;
        }

        $pivot = $this->journals()
            ->where('journals.id', $journal->id)
            ->first()?->pivot?->role;

        return $pivot ? (string) $pivot : null;
    }

    /**
     * Journals this user may manage (platform admin: all; otherwise pivot admin/editor only).
     *
     * @return \Illuminate\Support\Collection<int, Journal>
     */
    public function managedJournals()
    {
        if ($this->isAdmin()) {
            return Journal::query()->orderBy('title')->get();
        }

        return $this->journals()
            ->wherePivotIn('role', \App\Support\JournalTeamRoles::manageRoles())
            ->orderBy('title')
            ->get();
    }

    /**
     * Journals where this user holds any team role (admin/editor/reviewer).
     *
     * @return \Illuminate\Support\Collection<int, Journal>
     */
    public function staffJournals()
    {
        return $this->journals()
            ->wherePivotIn('role', \App\Support\JournalTeamRoles::all())
            ->orderBy('title')
            ->get();
    }

    public function canManageJournal(Journal $journal): bool
    {
        if ($this->isAdmin()) {
            return true;
        }

        return $this->journals()
            ->where('journals.id', $journal->id)
            ->wherePivotIn('role', \App\Support\JournalTeamRoles::manageRoles())
            ->exists();
    }

    public function canAccessPlatformAdmin(): bool
    {
        return $this->isAdmin();
    }

    public function isJournalStaffFor(Journal $journal): bool
    {
        if ($this->isAdmin()) {
            return true;
        }

        return $this->journals()
            ->where('journals.id', $journal->id)
            ->wherePivotIn('role', \App\Support\JournalTeamRoles::all())
            ->exists();
    }

    /**
     * Default landing route after login for this user.
     */
    public function homeRouteName(): string
    {
        if ($this->isAdmin()) {
            return 'admin.dashboard';
        }

        if ($this->managedJournals()->isNotEmpty()) {
            return 'journal.manage.dashboard';
        }

        if ($this->canAccessReviewQueue() && $this->role === 'reviewer') {
            return 'reviewer.reviews.index';
        }

        return 'dashboard';
    }

    /**
     * Named route parameters for homeRouteName() when the destination needs a journal.
     *
     * @return array<string, mixed>
     */
    public function homeRouteParameters(): array
    {
        if ($this->homeRouteName() === 'journal.manage.dashboard') {
            $journal = $this->managedJournals()->first();

            return $journal ? ['journal' => $journal] : [];
        }

        return [];
    }

    /**
     * Prefer preserving the current manage section when switching journals.
     */
    public function journalManageSwitchUrl(Journal $target, ?string $currentRouteName = null): string
    {
        $currentRouteName = $currentRouteName ?: request()->route()?->getName();
        $switchable = [
            'journal.manage.dashboard',
            'journal.manage.articles.index',
            'journal.manage.volumes.index',
            'journal.manage.submissions.index',
            'journal.manage.membership-plans.index',
            'journal.manage.settings.edit',
        ];

        if ($currentRouteName && in_array($currentRouteName, $switchable, true)) {
            try {
                return route($currentRouteName, ['journal' => $target]);
            } catch (\Throwable) {
                // fall through
            }
        }

        return route('journal.manage.dashboard', $target);
    }

    public function journals(): BelongsToMany
    {
        return $this->belongsToMany(Journal::class)->withPivot('role')->withTimestamps();
    }

    public function memberships(): HasMany
    {
        return $this->hasMany(Membership::class);
    }

    public function purchases(): HasMany
    {
        return $this->hasMany(Purchase::class);
    }

    public function submissions(): HasMany
    {
        return $this->hasMany(Submission::class, 'author_id');
    }
}
