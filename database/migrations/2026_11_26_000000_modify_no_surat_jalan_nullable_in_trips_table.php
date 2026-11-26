<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
<<<<<<< HEAD
use Illuminate\Support\Facades\DB;
=======
>>>>>>> origin/main
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
<<<<<<< HEAD
        // First, drop any existing unique constraints using raw SQL
        // This handles both 'trips' and 'daily_costs' tables
        try {
            DB::statement("ALTER TABLE trips DROP INDEX no_surat_jalan");
        } catch (\Exception $e) {
            // Index may not exist, continue
        }

        try {
            DB::statement("ALTER TABLE daily_costs DROP INDEX no_surat_jalan");
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
=======
        // Drop existing unique constraints first to avoid duplicate key error
        Schema::table('trips', function (Blueprint $table) {
            $table->dropUnique(['no_surat_jalan']);
        });

        Schema::table('daily_costs', function (Blueprint $table) {
            $table->dropUnique(['no_surat_jalan']);
        });

        // Now modify the columns to be nullable and add unique constraints
        Schema::table('trips', function (Blueprint $table) {
            $table->string('no_surat_jalan')->nullable()->unique()->change();
        });

        Schema::table('daily_costs', function (Blueprint $table) {
            $table->string('no_surat_jalan')->nullable()->unique()->change();
>>>>>>> origin/main
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
<<<<<<< HEAD
        // Remove unique constraints
=======
        // Drop existing constraints first
>>>>>>> origin/main
        Schema::table('trips', function (Blueprint $table) {
            $table->dropUnique(['no_surat_jalan']);
        });

        Schema::table('daily_costs', function (Blueprint $table) {
            $table->dropUnique(['no_surat_jalan']);
        });

<<<<<<< HEAD
        // Restore non-nullable columns
        Schema::table('trips', function (Blueprint $table) {
            $table->string('no_surat_jalan')->nullable(false)->change();
        });

        Schema::table('daily_costs', function (Blueprint $table) {
            $table->string('no_surat_jalan')->nullable(false)->change();
=======
        // Restore non-nullable with unique constraint
        Schema::table('trips', function (Blueprint $table) {
            $table->string('no_surat_jalan')->nullable(false)->unique()->change();
        });

        Schema::table('daily_costs', function (Blueprint $table) {
            $table->string('no_surat_jalan')->nullable(false)->unique()->change();
>>>>>>> origin/main
        });
    }
};