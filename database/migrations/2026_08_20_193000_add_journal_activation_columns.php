<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('journals', function (Blueprint $table) {
            $table->string('activation_status', 16)->default('active')->after('is_featured');
            $table->timestamp('activation_paid_at')->nullable()->after('activation_status');
            $table->timestamp('activation_expires_at')->nullable()->after('activation_paid_at');
            $table->json('activation_reminders_sent')->nullable()->after('activation_expires_at');
        });

        // Existing journals stay publicly listed (platform-waived; no expiry).
        DB::table('journals')->update([
            'activation_status' => 'active',
            'activation_paid_at' => now(),
            'activation_expires_at' => null,
            'activation_reminders_sent' => null,
        ]);
    }

    public function down(): void
    {
        Schema::table('journals', function (Blueprint $table) {
            $table->dropColumn([
                'activation_status',
                'activation_paid_at',
                'activation_expires_at',
                'activation_reminders_sent',
            ]);
        });
    }
};
