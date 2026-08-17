<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Normalize blank DOIs to null so a unique index allows multiple unpublished articles.
        DB::table('articles')->where('doi', '')->update(['doi' => null]);

        // Collapse accidental duplicate non-null DOIs by clearing older rows.
        $duplicates = DB::table('articles')
            ->select('doi')
            ->whereNotNull('doi')
            ->groupBy('doi')
            ->havingRaw('COUNT(*) > 1')
            ->pluck('doi');

        foreach ($duplicates as $doi) {
            $ids = DB::table('articles')
                ->where('doi', $doi)
                ->orderByDesc('updated_at')
                ->orderByDesc('id')
                ->pluck('id');

            $keep = $ids->shift();
            if ($ids->isNotEmpty()) {
                DB::table('articles')->whereIn('id', $ids)->update(['doi' => null]);
            }
            unset($keep);
        }

        Schema::table('articles', function (Blueprint $table) {
            $table->unique('doi');
        });
    }

    public function down(): void
    {
        Schema::table('articles', function (Blueprint $table) {
            $table->dropUnique(['doi']);
        });
    }
};
