<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('journals', function (Blueprint $table) {
            $table->string('payment_gateway_preference', 20)->default('unset')->after('activation_reminders_sent');
            // personal | split | unset
            $table->text('paystack_public_key')->nullable()->after('payment_gateway_preference');
            $table->text('paystack_secret_key')->nullable()->after('paystack_public_key');
            $table->string('paystack_split_code', 64)->nullable()->after('paystack_secret_key');
            $table->boolean('personal_gateway_allowed')->default(true)->after('paystack_split_code');
        });

        Schema::table('payment_transactions', function (Blueprint $table) {
            $table->string('gateway_mode', 20)->nullable()->after('provider'); // platform|personal|split
            $table->foreignId('gateway_journal_id')->nullable()->after('gateway_mode')->constrained('journals')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('payment_transactions', function (Blueprint $table) {
            $table->dropConstrainedForeignId('gateway_journal_id');
            $table->dropColumn('gateway_mode');
        });

        Schema::table('journals', function (Blueprint $table) {
            $table->dropColumn([
                'payment_gateway_preference',
                'paystack_public_key',
                'paystack_secret_key',
                'paystack_split_code',
                'personal_gateway_allowed',
            ]);
        });
    }
};
