<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('journals', function (Blueprint $table) {
            $table->id();
            $table->string('slug')->unique();
            $table->string('title');
            $table->string('subtitle')->nullable();
            $table->text('description')->nullable();
            $table->string('issn')->nullable();
            $table->string('eissn')->nullable();
            $table->string('publisher')->nullable();
            $table->string('default_license')->nullable();
            $table->string('language', 16)->default('en');
            $table->boolean('is_active')->default(true);
            $table->boolean('is_featured')->default(false);
            $table->unsignedInteger('membership_price')->nullable();
            $table->unsignedInteger('membership_days')->nullable();
            $table->string('cover_path')->nullable();
            $table->timestamps();
        });

        Schema::create('journal_user', function (Blueprint $table) {
            $table->id();
            $table->foreignId('journal_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('role', 32); // editor, reviewer
            $table->timestamps();
            $table->unique(['journal_id', 'user_id', 'role']);
        });

        Schema::create('editorial_board_members', function (Blueprint $table) {
            $table->id();
            $table->foreignId('journal_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('role_title')->nullable();
            $table->string('affiliation')->nullable();
            $table->string('email')->nullable();
            $table->string('orcid')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('volumes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('journal_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('volume_number');
            $table->unsignedSmallInteger('year');
            $table->string('title')->nullable();
            $table->text('introduction')->nullable();
            $table->string('issn')->nullable();
            $table->string('cover_path')->nullable();
            $table->string('status', 20)->default('draft'); // draft|published
            $table->timestamps();
            $table->unique(['journal_id', 'volume_number', 'year']);
        });

        Schema::create('issues', function (Blueprint $table) {
            $table->id();
            $table->foreignId('volume_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('issue_number');
            $table->string('title')->nullable();
            $table->date('period_start')->nullable();
            $table->date('period_end')->nullable();
            $table->string('cover_path')->nullable();
            $table->string('status', 20)->default('draft'); // draft|published
            $table->timestamps();
            $table->unique(['volume_id', 'issue_number']);
        });

        Schema::create('articles', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignId('journal_id')->constrained()->cascadeOnDelete();
            $table->foreignId('issue_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('submission_id')->nullable();
            $table->foreignId('author_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('slug');
            $table->string('title');
            $table->text('abstract')->nullable();
            $table->string('category')->nullable();
            $table->string('keywords')->nullable();
            $table->string('doi')->nullable();
            $table->string('license')->nullable();
            $table->string('page_range')->nullable();
            $table->string('visibility', 20)->default('open'); // open|members_only|paid|closed
            $table->unsignedInteger('price_amount')->nullable(); // minor units / whole NGN
            $table->string('currency', 8)->default('NGN');
            $table->string('document_path')->nullable();
            $table->string('status', 32)->default('draft'); // draft|published
            $table->timestamp('published_at')->nullable();
            $table->timestamp('last_accessed_at')->nullable();
            $table->unsignedInteger('mins_read')->nullable();
            $table->json('references')->nullable();
            $table->timestamps();
            $table->unique(['journal_id', 'slug']);
            $table->index(['visibility', 'status', 'published_at']);
        });

        Schema::create('article_authors', function (Blueprint $table) {
            $table->id();
            $table->uuid('article_id');
            $table->foreign('article_id')->references('id')->on('articles')->cascadeOnDelete();
            $table->string('name');
            $table->string('email')->nullable();
            $table->string('affiliation')->nullable();
            $table->string('nationality')->nullable();
            $table->string('orcid')->nullable();
            $table->string('role')->nullable();
            $table->boolean('is_corresponding')->default(false);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('article_authors');
        Schema::dropIfExists('articles');
        Schema::dropIfExists('issues');
        Schema::dropIfExists('volumes');
        Schema::dropIfExists('editorial_board_members');
        Schema::dropIfExists('journal_user');
        Schema::dropIfExists('journals');
    }
};
