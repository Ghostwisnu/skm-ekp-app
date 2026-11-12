<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Idempotent: skip if table already exists
        $tables = DB::select("SHOW TABLES LIKE 'daily_cost_extras'");
        if (empty($tables)) {
            Schema::create('daily_cost_extras', function (Blueprint $table) {
                $table->id();
                $table->foreignId('daily_cost_id')->constrained()->cascadeOnDelete();
                $table->string('nama_biaya', 255);
                $table->unsignedBigInteger('nominal')->default(0);
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('daily_cost_extras');
    }
};