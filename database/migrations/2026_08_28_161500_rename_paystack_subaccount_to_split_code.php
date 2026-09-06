<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('journals', 'paystack_split_code')) {
            if (Schema::hasColumn('journals', 'paystack_subaccount_code')) {
                $this->copyLegacySplitValues();
                Schema::table('journals', function (Blueprint $table) {
                    $table->dropColumn('paystack_subaccount_code');
                });
            }

            return;
        }

        Schema::table('journals', function (Blueprint $table) {
            $table->string('paystack_split_code', 64)->nullable()->after('paystack_secret_key');
        });

        if (Schema::hasColumn('journals', 'paystack_subaccount_code')) {
            $this->copyLegacySplitValues();

            Schema::table('journals', function (Blueprint $table) {
                $table->dropColumn('paystack_subaccount_code');
            });
        }
    }

    public function down(): void
    {
        if (! Schema::hasColumn('journals', 'paystack_subaccount_code')) {
            Schema::table('journals', function (Blueprint $table) {
                $table->string('paystack_subaccount_code')->nullable()->after('paystack_secret_key');
            });
        }

        if (Schema::hasColumn('journals', 'paystack_split_code')) {
            foreach (DB::table('journals')->orderBy('id')->get() as $row) {
                if (filled($row->paystack_split_code)) {
                    DB::table('journals')->where('id', $row->id)->update([
                        'paystack_subaccount_code' => $row->paystack_split_code,
                    ]);
                }
            }

            Schema::table('journals', function (Blueprint $table) {
                $table->dropColumn('paystack_split_code');
            });
        }
    }

    private function copyLegacySplitValues(): void
    {
        foreach (DB::table('journals')->orderBy('id')->get() as $row) {
            $legacy = $row->paystack_subaccount_code ?? null;
            if (! filled($legacy)) {
                continue;
            }

            // Keep only values that already look like Paystack split codes.
            if (str_starts_with(strtoupper((string) $legacy), 'SPL_')) {
                DB::table('journals')->where('id', $row->id)->update([
                    'paystack_split_code' => $legacy,
                ]);
            }
        }
    }
};
