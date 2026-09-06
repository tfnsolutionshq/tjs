<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('journals', function (Blueprint $table) {
            $table->string('doi_mode', 20)->default('unset')->after('personal_gateway_allowed');
            // unset | platform | own
            $table->string('doi_prefix')->nullable()->after('doi_mode');
            $table->text('crossref_username')->nullable()->after('doi_prefix');
            $table->text('crossref_password')->nullable()->after('crossref_username');
            $table->unsignedInteger('doi_credits_balance')->default(0)->after('crossref_password');
            $table->unsignedInteger('doi_credits_lifetime')->default(0)->after('doi_credits_balance');
            $table->boolean('doi_auto_deposit')->default(false)->after('doi_credits_lifetime');
            $table->timestamp('doi_low_balance_notified_at')->nullable()->after('doi_auto_deposit');
            $table->timestamp('doi_exhausted_notified_at')->nullable()->after('doi_low_balance_notified_at');
        });

        Schema::table('articles', function (Blueprint $table) {
            $table->string('doi_deposit_status', 20)->nullable()->after('doi');
            // null|pending|deposited|failed
            $table->timestamp('doi_deposited_at')->nullable()->after('doi_deposit_status');
        });

        Schema::create('doi_credit_topups', function (Blueprint $table) {
            $table->id();
            $table->foreignId('journal_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('credits');
            $table->decimal('amount_usd', 12, 2)->nullable();
            $table->unsignedInteger('amount_ngn')->nullable();
            $table->string('note')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('doi_deposits', function (Blueprint $table) {
            $table->id();
            $table->foreignId('journal_id')->constrained()->cascadeOnDelete();
            $table->uuid('article_id');
            $table->foreign('article_id')->references('id')->on('articles')->cascadeOnDelete();
            $table->string('doi');
            $table->string('mode', 20); // platform|own
            $table->string('status', 20)->default('pending'); // pending|success|failed
            $table->unsignedTinyInteger('credits_spent')->default(0);
            $table->json('provider_payload')->nullable();
            $table->text('error_message')->nullable();
            $table->foreignId('deposited_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('deposited_at')->nullable();
            $table->timestamps();
            $table->index(['journal_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('doi_deposits');
        Schema::dropIfExists('doi_credit_topups');

        Schema::table('articles', function (Blueprint $table) {
            $table->dropColumn(['doi_deposit_status', 'doi_deposited_at']);
        });

        Schema::table('journals', function (Blueprint $table) {
            $table->dropColumn([
                'doi_mode',
                'doi_prefix',
                'crossref_username',
                'crossref_password',
                'doi_credits_balance',
                'doi_credits_lifetime',
                'doi_auto_deposit',
                'doi_low_balance_notified_at',
                'doi_exhausted_notified_at',
            ]);
        });
    }
};
