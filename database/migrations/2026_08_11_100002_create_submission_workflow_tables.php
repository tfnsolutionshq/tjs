<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('submissions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignId('journal_id')->constrained()->cascadeOnDelete();
            $table->foreignId('author_id')->constrained('users')->cascadeOnDelete();
            $table->string('title');
            $table->text('abstract')->nullable();
            $table->string('category')->nullable();
            $table->string('keywords')->nullable();
            $table->string('document_path')->nullable();
            $table->string('status', 32)->default('submitted');
            // submitted|under_review|revision_requested|resubmitted|approved|rejected
            $table->foreignId('reviewer_id')->nullable()->constrained('users')->nullOnDelete();
            $table->text('review_comment')->nullable();
            $table->text('rejection_reason')->nullable();
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamps();
            $table->index(['journal_id', 'status']);
        });

        Schema::table('articles', function (Blueprint $table) {
            $table->foreign('submission_id')->references('id')->on('submissions')->nullOnDelete();
        });

        Schema::create('reviewer_assignments', function (Blueprint $table) {
            $table->id();
            $table->uuid('submission_id');
            $table->foreign('submission_id')->references('id')->on('submissions')->cascadeOnDelete();
            $table->foreignId('reviewer_id')->constrained('users')->cascadeOnDelete();
            $table->string('status', 32)->default('assigned');
            $table->unsignedTinyInteger('priority')->default(3);
            $table->timestamp('due_at')->nullable();
            $table->timestamps();
        });

        Schema::create('submission_timelines', function (Blueprint $table) {
            $table->id();
            $table->uuid('submission_id');
            $table->foreign('submission_id')->references('id')->on('submissions')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('event');
            $table->json('metadata')->nullable();
            $table->timestamps();
        });

        Schema::create('editorial_comments', function (Blueprint $table) {
            $table->id();
            $table->uuid('submission_id');
            $table->foreign('submission_id')->references('id')->on('submissions')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('parent_id')->nullable()->constrained('editorial_comments')->nullOnDelete();
            $table->text('body');
            $table->timestamps();
        });

        Schema::create('submission_revisions', function (Blueprint $table) {
            $table->id();
            $table->uuid('submission_id');
            $table->foreign('submission_id')->references('id')->on('submissions')->cascadeOnDelete();
            $table->foreignId('uploaded_by')->constrained('users')->cascadeOnDelete();
            $table->unsignedInteger('revision_number');
            $table->string('document_path');
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('submission_revisions');
        Schema::dropIfExists('editorial_comments');
        Schema::dropIfExists('submission_timelines');
        Schema::dropIfExists('reviewer_assignments');
        Schema::table('articles', function (Blueprint $table) {
            $table->dropForeign(['submission_id']);
        });
        Schema::dropIfExists('submissions');
    }
};
