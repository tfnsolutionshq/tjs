<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('issues', function (Blueprint $table) {
            $table->foreignId('journal_fee_id')
                ->nullable()
                ->after('status')
                ->constrained('journal_fees')
                ->nullOnDelete();
        });

        Schema::table('submissions', function (Blueprint $table) {
            $table->foreignId('publication_journal_fee_id')
                ->nullable()
                ->after('fee_payment_transaction_id')
                ->constrained('journal_fees')
                ->nullOnDelete();

            $table->foreignId('publication_fee_payment_transaction_id')
                ->nullable()
                ->after('publication_journal_fee_id')
                ->constrained('payment_transactions')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('submissions', function (Blueprint $table) {
            $table->dropConstrainedForeignId('publication_fee_payment_transaction_id');
            $table->dropConstrainedForeignId('publication_journal_fee_id');
        });

        Schema::table('issues', function (Blueprint $table) {
            $table->dropConstrainedForeignId('journal_fee_id');
        });
    }
};
