<?php

use App\Models\Article;
use App\Models\Category;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('categories', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->string('slug')->unique();
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('article_category', function (Blueprint $table) {
            $table->id();
            $table->uuid('article_id');
            $table->foreignId('category_id')->constrained()->cascadeOnDelete();
            $table->timestamps();

            $table->foreign('article_id')->references('id')->on('articles')->cascadeOnDelete();
            $table->unique(['article_id', 'category_id']);
        });

        $defaults = [
            'Research Article',
            'Review',
            'Editorial',
            'Case Study',
            'Commentary',
            'Short Communication',
            'Letter',
            'Perspective',
        ];

        foreach ($defaults as $i => $name) {
            Category::query()->firstOrCreate(
                ['slug' => Str::slug($name)],
                ['name' => $name, 'is_active' => true, 'sort_order' => $i + 1]
            );
        }

        Article::query()
            ->whereNotNull('category')
            ->where('category', '!=', '')
            ->orderBy('id')
            ->each(function (Article $article) {
                $names = preg_split('/\s*[,;|]\s*/', (string) $article->category) ?: [];
                $ids = [];
                foreach ($names as $name) {
                    $name = trim($name);
                    if ($name === '') {
                        continue;
                    }
                    $category = Category::query()->firstOrCreate(
                        ['slug' => Str::slug($name)],
                        ['name' => $name, 'is_active' => true, 'sort_order' => 100]
                    );
                    $ids[] = $category->id;
                }
                if ($ids !== []) {
                    $article->categories()->sync(array_values(array_unique($ids)));
                }
            });
    }

    public function down(): void
    {
        Schema::dropIfExists('article_category');
        Schema::dropIfExists('categories');
    }
};
