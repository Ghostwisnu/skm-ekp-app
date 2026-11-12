<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;

return new class extends Migration
{
    public function up(): void
    {
        // Check if table exists using information_schema (works on all MySQL versions)
        $result = DB::select(
            "SELECT COUNT(*) as cnt FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = 'daily_cost_extras'"
        );

        if ((int) ($result[0]->cnt ?? 0) === 0) {
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