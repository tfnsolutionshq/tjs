<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('submission_production_files', function (Blueprint $table) {
            $table->id();
            $table->uuid('submission_id');
            $table->foreign('submission_id')->references('id')->on('submissions')->cascadeOnDelete();
            $table->foreignId('uploaded_by')->constrained('users')->cascadeOnDelete();
            $table->unsignedInteger('version')->default(1);
            $table->string('document_path');
            $table->string('document_disk', 32)->nullable();
            $table->string('original_filename')->nullable();
            $table->boolean('is_current')->default(true);
            $table->timestamps();

            $table->index(['submission_id', 'is_current']);
        });

        Schema::table('submissions', function (Blueprint $table) {
            $table->foreignId('production_assigned_to')->nullable()->after('reviewed_at')->constrained('users')->nullOnDelete();
            $table->timestamp('production_started_at')->nullable()->after('production_assigned_to');
            $table->timestamp('production_completed_at')->nullable()->after('production_started_at');
            $table->foreignId('production_completed_by')->nullable()->after('production_completed_at')->constrained('users')->nullOnDelete();
            $table->json('production_checklist')->nullable()->after('production_completed_by');
        });

        // Accepted manuscripts awaiting publication: map legacy "approved" to production queue.
        DB::table('submissions')
            ->where('status', 'approved')
            ->whereNotExists(function ($query) {
                $query->select(DB::raw(1))
                    ->from('articles')
                    ->whereColumn('articles.submission_id', 'submissions.id');
            })
            ->update(['status' => 'ready_for_production']);
    }

    public function down(): void
    {
        DB::table('submissions')
            ->where('status', 'ready_for_production')
            ->update(['status' => 'approved']);

        Schema::table('submissions', function (Blueprint $table) {
            $table->dropConstrainedForeignId('production_completed_by');
            $table->dropColumn(['production_started_at', 'production_completed_at', 'production_checklist']);
            $table->dropConstrainedForeignId('production_assigned_to');
        });

        Schema::dropIfExists('submission_production_files');
    }
};
