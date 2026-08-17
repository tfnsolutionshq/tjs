<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('journal_announcements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('journal_id')->constrained()->cascadeOnDelete();
            $table->foreignId('issue_id')->nullable()->constrained()->nullOnDelete();
            $table->string('type', 32);
            $table->string('title');
            $table->string('summary')->nullable();
            $table->text('body')->nullable();
            $table->timestamp('opens_at')->nullable();
            $table->timestamp('closes_at')->nullable();
            $table->boolean('is_published')->default(false);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['journal_id', 'type', 'is_published']);
            $table->index(['journal_id', 'closes_at']);
        });

        Schema::table('submissions', function (Blueprint $table) {
            $table->foreignId('issue_id')->nullable()->after('journal_id')->constrained()->nullOnDelete();
            $table->foreignId('announcement_id')->nullable()->after('issue_id')->constrained('journal_announcements')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('submissions', function (Blueprint $table) {
            $table->dropConstrainedForeignId('announcement_id');
            $table->dropConstrainedForeignId('issue_id');
        });

        Schema::dropIfExists('journal_announcements');
    }
};
