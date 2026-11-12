<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Only drop if the column actually exists (idempotent)
        $columns = DB::select("PRAGMA table_info(users)");
        $hasRole = false;
        foreach ($columns as $col) {
            if ($col->name === 'role') {
                $hasRole = true;
                break;
            }
        }

        if ($hasRole) {
            Schema::table('users', function (Blueprint $table) {
                $table->dropColumn('role');
            });
        }
    }

    public function down(): void
    {
        // Only add if not exists (idempotent)
        $columns = DB::select("PRAGMA table_info(users)");
        $hasRole = false;
        foreach ($columns as $col) {
            if ($col->name === 'role') {
                $hasRole = true;
                break;
            }
        }

        if (!$hasRole) {
            Schema::table('users', function (Blueprint $table) {
                $table->string('role')->default('operator')->after('password')->index();
            });
        }
    }
};