<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('membership_plans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('journal_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name');
            $table->string('scope', 20)->default('platform'); // platform|journal
            $table->unsignedInteger('price_amount');
            $table->string('currency', 8)->default('NGN');
            $table->unsignedInteger('duration_days')->default(365);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('memberships', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('membership_plan_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('journal_id')->nullable()->constrained()->nullOnDelete();
            $table->string('scope', 20)->default('platform');
            $table->string('status', 20)->default('active'); // active|expired|revoked
            $table->timestamp('starts_at');
            $table->timestamp('ends_at');
            $table->timestamps();
            $table->index(['user_id', 'status', 'ends_at']);
        });

        Schema::create('payment_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('reference')->unique();
            $table->string('payable_type');
            $table->string('payable_id');
            $table->unsignedInteger('amount');
            $table->string('currency', 8)->default('NGN');
            $table->string('status', 20)->default('pending'); // pending|success|failed
            $table->string('provider', 32)->default('paystack');
            $table->json('provider_payload')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();
            $table->index(['payable_type', 'payable_id']);
        });

        Schema::create('purchases', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->uuid('article_id');
            $table->foreign('article_id')->references('id')->on('articles')->cascadeOnDelete();
            $table->foreignId('payment_transaction_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamp('revoked_at')->nullable();
            $table->timestamps();
            $table->unique(['user_id', 'article_id']);
        });

        Schema::create('access_logs', function (Blueprint $table) {
            $table->id();
            $table->uuid('article_id')->nullable();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('action', 64);
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->index(['article_id', 'action']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('access_logs');
        Schema::dropIfExists('purchases');
        Schema::dropIfExists('payment_transactions');
        Schema::dropIfExists('memberships');
        Schema::dropIfExists('membership_plans');
    }
};
