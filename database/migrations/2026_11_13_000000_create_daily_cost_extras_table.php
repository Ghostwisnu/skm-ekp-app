<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\QueryException;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Idempotent: safely create if not exists
        $this->createDailyCostExtras();
    }

    private function createDailyCostExtras(): void
    {
        if (Schema::hasTable('daily_cost_extras')) {
            return;
        }

        try {
            Schema::create('daily_cost_extras', function (Blueprint $table) {
                $table->id();
                $table->foreignId('daily_cost_id')->constrained()->cascadeOnDelete();
                $table->string('nama_biaya', 255);
                $table->unsignedBigInteger('nominal')->default(0);
                $table->timestamps();
            });
        } catch (QueryException $e) {
            // Tolerate a concurrent create of the same table, rethrow everything else.
            if (($e->errorInfo[0] ?? null) !== '42S01') {
                throw $e;
            }
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('daily_cost_extras');
    }
};
