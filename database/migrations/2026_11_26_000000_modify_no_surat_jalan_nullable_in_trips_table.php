<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
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
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Drop existing constraints first
        Schema::table('trips', function (Blueprint $table) {
            $table->dropUnique(['no_surat_jalan']);
        });

        Schema::table('daily_costs', function (Blueprint $table) {
            $table->dropUnique(['no_surat_jalan']);
        });

        // Restore non-nullable with unique constraint
        Schema::table('trips', function (Blueprint $table) {
            $table->string('no_surat_jalan')->nullable(false)->unique()->change();
        });

        Schema::table('daily_costs', function (Blueprint $table) {
            $table->string('no_surat_jalan')->nullable(false)->unique()->change();
        });
    }
};