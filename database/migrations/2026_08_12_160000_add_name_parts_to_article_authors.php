<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('article_authors', function (Blueprint $table) {
            $table->string('surname')->nullable()->after('name');
            $table->string('given_names')->nullable()->after('surname');
            $table->string('middle_name')->nullable()->after('given_names');
        });
    }

    public function down(): void
    {
        Schema::table('article_authors', function (Blueprint $table) {
            $table->dropColumn(['surname', 'given_names', 'middle_name']);
        });
    }
};
