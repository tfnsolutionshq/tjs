<?php

use App\Models\Category;
use App\Models\Journal;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('categories', 'journal_id')) {
            Schema::table('categories', function (Blueprint $table) {
                $table->foreignId('journal_id')->nullable()->after('id')->constrained()->cascadeOnDelete();
            });
        }

        // Drop global uniqueness before cloning templates per journal.
        foreach ([['name'], ['slug']] as $columns) {
            try {
                Schema::table('categories', function (Blueprint $table) use ($columns) {
                    $table->dropUnique($columns);
                });
            } catch (\Throwable) {
                // Index may already be gone after a partial migrate attempt.
            }
        }

        $defaults = Category::query()
            ->whereNull('journal_id')
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get(['id', 'name', 'slug', 'is_active', 'sort_order']);

        $journalMap = [];

        foreach (Journal::query()->orderBy('id')->cursor() as $journal) {
            $journalMap[$journal->id] = [];

            foreach ($defaults as $template) {
                $slug = $template->slug ?: Str::slug($template->name);
                $created = Category::query()->firstOrCreate(
                    [
                        'journal_id' => $journal->id,
                        'slug' => $slug,
                    ],
                    [
                        'name' => $template->name,
                        'is_active' => (bool) $template->is_active,
                        'sort_order' => (int) $template->sort_order,
                    ]
                );

                $journalMap[$journal->id][$slug] = $created->id;
                $journalMap[$journal->id]['id:'.$template->id] = $created->id;
                $journalMap[$journal->id]['name:'.Str::lower($template->name)] = $created->id;
            }
        }

        $pivots = DB::table('article_category')
            ->join('articles', 'articles.id', '=', 'article_category.article_id')
            ->select(
                'article_category.id as pivot_id',
                'article_category.article_id',
                'article_category.category_id',
                'articles.journal_id'
            )
            ->get();

        foreach ($pivots as $pivot) {
            $journalId = (int) $pivot->journal_id;
            $map = $journalMap[$journalId] ?? [];
            $newId = $map['id:'.$pivot->category_id] ?? null;

            if (! $newId) {
                $old = Category::query()->find($pivot->category_id);
                if ($old) {
                    $slug = $old->slug ?: Str::slug($old->name);
                    $created = Category::query()->firstOrCreate(
                        [
                            'journal_id' => $journalId,
                            'slug' => $slug,
                        ],
                        [
                            'name' => $old->name,
                            'is_active' => (bool) $old->is_active,
                            'sort_order' => (int) $old->sort_order,
                        ]
                    );
                    $newId = $created->id;
                    $journalMap[$journalId][$slug] = $created->id;
                    $journalMap[$journalId]['id:'.$old->id] = $created->id;
                }
            }

            if (! $newId || (int) $newId === (int) $pivot->category_id) {
                continue;
            }

            $exists = DB::table('article_category')
                ->where('article_id', $pivot->article_id)
                ->where('category_id', $newId)
                ->exists();

            if ($exists) {
                DB::table('article_category')->where('id', $pivot->pivot_id)->delete();
            } else {
                DB::table('article_category')
                    ->where('id', $pivot->pivot_id)
                    ->update(['category_id' => $newId]);
            }
        }

        Category::query()->whereNull('journal_id')->delete();

        foreach ([['journal_id', 'name'], ['journal_id', 'slug']] as $columns) {
            try {
                Schema::table('categories', function (Blueprint $table) use ($columns) {
                    $table->unique($columns);
                });
            } catch (\Throwable) {
                // Already present after a partial migrate attempt.
            }
        }
    }

    public function down(): void
    {
        foreach ([['journal_id', 'name'], ['journal_id', 'slug']] as $columns) {
            try {
                Schema::table('categories', function (Blueprint $table) use ($columns) {
                    $table->dropUnique($columns);
                });
            } catch (\Throwable) {
                // ignore
            }
        }

        if (Schema::hasColumn('categories', 'journal_id')) {
            Schema::table('categories', function (Blueprint $table) {
                $table->dropConstrainedForeignId('journal_id');
            });
        }

        Schema::table('categories', function (Blueprint $table) {
            $table->unique('name');
            $table->unique('slug');
        });
    }
};
