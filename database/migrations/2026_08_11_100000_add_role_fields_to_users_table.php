<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('role', 32)->default('member')->after('email');
            $table->string('affiliation')->nullable()->after('role');
            $table->string('orcid')->nullable()->after('affiliation');
            $table->text('bio')->nullable()->after('orcid');
            $table->string('position')->nullable()->after('bio');
            $table->boolean('is_public_reviewer')->default(false)->after('position');
            $table->index('role');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex(['role']);
            $table->dropColumn(['role', 'affiliation', 'orcid', 'bio', 'position', 'is_public_reviewer']);
        });
    }
};
