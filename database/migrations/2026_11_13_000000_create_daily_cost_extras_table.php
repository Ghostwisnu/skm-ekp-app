<?php

use Illuminate\Database\QueryException;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('daily_cost_extras')) {
            try {
                Schema::create('daily_cost_extras', function (Blueprint $table) {
                    $table->id();
                    $table->foreignId('daily_cost_id')->constrained()->cascadeOnDelete();
                    $table->string('nama_biaya', 255);
                    $table->unsignedBigInteger('nominal')->default(0);
                    $table->timestamps();
                });
            } catch (QueryException $exception) {
                if (($exception->errorInfo[0] ?? null) !== '42S01' || ($exception->errorInfo[1] ?? null) !== 1050) {
                    throw $exception;
                }
            }
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('daily_cost_extras');
    }
};