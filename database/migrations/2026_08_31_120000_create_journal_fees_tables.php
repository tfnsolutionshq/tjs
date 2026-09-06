<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('journal_fees', function (Blueprint $table) {
            $table->id();
            $table->foreignId('journal_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('purpose', 32);
            $table->unsignedInteger('amount');
            $table->string('currency', 8)->default('NGN');
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['journal_id', 'purpose', 'is_active']);
        });

        Schema::table('membership_plans', function (Blueprint $table) {
            $table->foreignId('journal_fee_id')->nullable()->after('journal_id')->constrained('journal_fees')->nullOnDelete();
        });

        Schema::table('submissions', function (Blueprint $table) {
            $table->foreignId('journal_fee_id')->nullable()->after('announcement_id')->constrained('journal_fees')->nullOnDelete();
            $table->foreignId('fee_payment_transaction_id')->nullable()->after('journal_fee_id')->constrained('payment_transactions')->nullOnDelete();
        });

        Schema::table('articles', function (Blueprint $table) {
            $table->foreignId('journal_fee_id')->nullable()->after('visibility')->constrained('journal_fees')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('articles', function (Blueprint $table) {
            $table->dropConstrainedForeignId('journal_fee_id');
        });

        Schema::table('submissions', function (Blueprint $table) {
            $table->dropConstrainedForeignId('fee_payment_transaction_id');
            $table->dropConstrainedForeignId('journal_fee_id');
        });

        Schema::table('membership_plans', function (Blueprint $table) {
            $table->dropConstrainedForeignId('journal_fee_id');
        });

        Schema::dropIfExists('journal_fees');
    }
};
