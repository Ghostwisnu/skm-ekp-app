<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // First, drop any existing unique constraints using raw SQL
        // This handles both 'trips' and 'daily_costs' tables
        // Use the correct index names: trips_no_surat_jalan_unique and daily_costs_no_surat_jalan_unique
        // SQLite uses DROP INDEX index_name (not ALTER TABLE DROP INDEX)
        try {
            DB::statement("DROP INDEX IF EXISTS trips_no_surat_jalan_unique");
        } catch (\Exception $e) {
            // Index may not exist, continue
        }

        try {
            DB::statement("DROP INDEX IF EXISTS daily_costs_no_surat_jalan_unique");
        } catch (\Exception $e) {
            // Index may not exist, continue
        }

        // Now modify columns to be nullable
        Schema::table('trips', function (Blueprint $table) {
            $table->string('no_surat_jalan')->nullable()->change();
        });

        Schema::table('daily_costs', function (Blueprint $table) {
            $table->string('no_surat_jalan')->nullable()->change();
        });

        // Finally, add back unique constraints
        Schema::table('trips', function (Blueprint $table) {
            $table->unique('no_surat_jalan');
        });

        Schema::table('daily_costs', function (Blueprint $table) {
            $table->unique('no_surat_jalan');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Remove unique constraints
        Schema::table('trips', function (Blueprint $table) {
            $table->dropUnique(['no_surat_jalan']);
        });

        Schema::table('daily_costs', function (Blueprint $table) {
            $table->dropUnique(['no_surat_jalan']);
        });

        // Restore non-nullable columns
        Schema::table('trips', function (Blueprint $table) {
            $table->string('no_surat_jalan')->nullable(false)->change();
        });

        Schema::table('daily_costs', function (Blueprint $table) {
            $table->string('no_surat_jalan')->nullable(false)->change();
        });
    }
};