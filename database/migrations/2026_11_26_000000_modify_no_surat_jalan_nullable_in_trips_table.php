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
        // Use try-catch to handle cases where index doesn't exist
        $this->dropUniqueIfExists('trips', 'no_surat_jalan');
        $this->dropUniqueIfExists('daily_costs', 'no_surat_jalan');

        // Now modify the columns to be nullable and add unique constraints
        Schema::table('trips', function (Blueprint $table) {
            if (Schema::hasColumn('trips', 'no_surat_jalan')) {
                $table->string('no_surat_jalan')->nullable()->unique()->change();
            }
        });

        Schema::table('daily_costs', function (Blueprint $table) {
            if (Schema::hasColumn('daily_costs', 'no_surat_jalan')) {
                $table->string('no_surat_jalan')->nullable()->unique()->change();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Drop existing constraints first
        $this->dropUniqueIfExists('trips', 'no_surat_jalan');
        $this->dropUniqueIfExists('daily_costs', 'no_surat_jalan');

        // Restore non-nullable with unique constraint
        Schema::table('trips', function (Blueprint $table) {
            if (Schema::hasColumn('trips', 'no_surat_jalan')) {
                $table->string('no_surat_jalan')->nullable(false)->unique()->change();
            }
        });

        Schema::table('daily_costs', function (Blueprint $table) {
            if (Schema::hasColumn('daily_costs', 'no_surat_jalan')) {
                $table->string('no_surat_jalan')->nullable(false)->unique()->change();
            }
        });
    }

    /**
     * Drop a unique index if it exists to avoid errors when migration is run
     * on databases where the index may not have been created with the expected name.
     */
    protected function dropUniqueIfExists(string $table, string $column): void
    {
        // First check if table exists
        if (!Schema::hasTable($table)) {
            return;
        }

        // Check if column exists
        if (!Schema::hasColumn($table, $column)) {
            return;
        }

        try {
            Schema::table($table, function (Blueprint $table) use ($column) {
                $table->dropUnique([$column]);
            });
        } catch (\Exception $e) {
            // Index may not exist or have a different name - continue
            $message = $e->getMessage();
            if (str_contains($message, 'doesn\'t exist') ||
                str_contains($message, 'Check that it exists') ||
                str_contains($message, 'no such index') ||
                str_contains($message, 'Can\'t DROP INDEX')) {
                return;
            }
            throw $e;
        }
    }
};