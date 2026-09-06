<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('articles', function (Blueprint $table) {
            $table->string('document_disk', 32)->nullable()->after('document_path');
        });

        Schema::table('submissions', function (Blueprint $table) {
            $table->string('document_disk', 32)->nullable()->after('document_path');
        });

        Schema::table('submission_revisions', function (Blueprint $table) {
            $table->string('document_disk', 32)->nullable()->after('document_path');
        });

        Schema::table('journals', function (Blueprint $table) {
            $table->string('logo_disk', 32)->nullable()->after('logo_path');
            $table->string('header_image_disk', 32)->nullable()->after('header_image_path');
        });

        Schema::table('volumes', function (Blueprint $table) {
            $table->string('cover_disk', 32)->nullable()->after('cover_path');
        });

        Schema::table('issues', function (Blueprint $table) {
            $table->string('cover_disk', 32)->nullable()->after('cover_path');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->string('avatar_disk', 32)->nullable()->after('avatar_path');
        });
    }

    public function down(): void
    {
        Schema::table('articles', function (Blueprint $table) {
            $table->dropColumn('document_disk');
        });
        Schema::table('submissions', function (Blueprint $table) {
            $table->dropColumn('document_disk');
        });
        Schema::table('submission_revisions', function (Blueprint $table) {
            $table->dropColumn('document_disk');
        });
        Schema::table('journals', function (Blueprint $table) {
            $table->dropColumn(['logo_disk', 'header_image_disk']);
        });
        Schema::table('volumes', function (Blueprint $table) {
            $table->dropColumn('cover_disk');
        });
        Schema::table('issues', function (Blueprint $table) {
            $table->dropColumn('cover_disk');
        });
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('avatar_disk');
        });
    }
};
