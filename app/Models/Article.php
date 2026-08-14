<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Article extends Model
{
    use HasUuids;

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'journal_id', 'issue_id', 'submission_id', 'author_user_id', 'slug', 'title',
        'abstract', 'category', 'keywords', 'doi', 'license', 'page_range',
        'visibility', 'price_amount', 'currency', 'document_path', 'status',
        'published_at', 'last_accessed_at', 'mins_read', 'references',
    ];

    protected function casts(): array
    {
        return [
            'published_at' => 'datetime',
            'last_accessed_at' => 'datetime',
            'references' => 'array',
            'price_amount' => 'integer',
        ];
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public function journal(): BelongsTo
    {
        return $this->belongsTo(Journal::class);
    }

    public function issue(): BelongsTo
    {
        return $this->belongsTo(Issue::class);
    }

    public function authors(): HasMany
    {
        return $this->hasMany(ArticleAuthor::class)->orderBy('sort_order');
    }

    public function categories(): BelongsToMany
    {
        return $this->belongsToMany(Category::class)->withTimestamps();
    }

    public function categoryLabels(): string
    {
        $this->loadMissing('categories');

        if ($this->categories->isNotEmpty()) {
            return $this->categories->pluck('name')->join(', ');
        }

        return (string) ($this->attributes['category'] ?? '');
    }

    public function purchases(): HasMany
    {
        return $this->hasMany(Purchase::class);
    }

    public function submission(): BelongsTo
    {
        return $this->belongsTo(Submission::class);
    }

    public function authorUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'author_user_id');
    }

    public function scopePublicCatalog(Builder $query): Builder
    {
        return $query
            ->where('articles.status', 'published')
            ->whereNotNull('articles.published_at')
            ->where('articles.visibility', '!=', 'closed')
            ->whereHas('journal', fn ($q) => $q->where('is_active', true))
            ->whereHas('issue', function ($q) {
                $q->where('status', 'published')
                    ->whereHas('volume', fn ($v) => $v->where('status', 'published'));
            });
    }

    public function isOpenAccess(): bool
    {
        return $this->visibility === 'open';
    }

    public function publicUrl(): string
    {
        return route('journals.articles.show', [$this->journal, $this]);
    }

    public function pdfUrl(): string
    {
        return route('journals.articles.pdf', [$this->journal, $this->slug]);
    }

    public function pdfViewerUrl(): string
    {
        return route('journals.articles.pdf-viewer', [$this->journal, $this]);
    }
}
