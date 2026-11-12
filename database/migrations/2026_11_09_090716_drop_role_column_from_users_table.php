<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Drop index first (MySQL requires this before dropping column)
        try {
            DB::statement('ALTER TABLE users DROP INDEX role');
        } catch (\Exception $e) {
            // Index doesn't exist, continue
        }

        // Drop column if exists
        try {
            Schema::table('users', function (Blueprint $table) {
                $table->dropColumn('role');
            });
        } catch (\Exception $e) {
            // Column doesn't exist, continue
        }
    }

    public function down(): void
    {
        // Only add if not exists
        if (!Schema::hasColumn('users', 'role')) {
            Schema::table('users', function (Blueprint $table) {
                $table->string('role')->default('operator')->after('password')->index();
            });
        }
    }
};