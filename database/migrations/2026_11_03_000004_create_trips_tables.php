<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('trips', function (Blueprint $table) {
            $table->id();
            $table->date('tanggal')->index();
            $table->foreignId('vehicle_id')->constrained()->restrictOnDelete();
            $table->foreignId('client_id')->constrained()->restrictOnDelete();
            $table->foreignId('invoice_id')->nullable()->constrained()->nullOnDelete();
            $table->string('sopir');
            $table->string('no_surat_jalan')->unique();
            $table->timestamps();
        });

        Schema::create('trip_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('trip_id')->constrained()->cascadeOnDelete();
            $table->string('jenis_barang');
            $table->decimal('tonase_kg', 12, 2);
            $table->timestamps();
        });

        Schema::create('daily_costs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('trip_id')->unique()->constrained()->cascadeOnDelete();
            $table->unsignedBigInteger('uang_jalan_1')->default(0);
            $table->unsignedBigInteger('uang_bongkar')->default(0);
            $table->unsignedBigInteger('bonus_tepat_waktu')->default(0);
            $table->unsignedBigInteger('bonus_tambahan')->default(0);
            $table->unsignedBigInteger('pinjaman')->default(0);
            $table->unsignedBigInteger('uang_jalan_2')->default(0);
            $table->unsignedBigInteger('simpanan')->default(0);
            $table->timestamps();
        });

        Schema::create('invoice_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('invoice_id')->constrained()->cascadeOnDelete();
            $table->foreignId('trip_id')->constrained()->restrictOnDelete();
            $table->foreignId('trip_item_id')->constrained()->restrictOnDelete();
            $table->string('no_surat_jalan');
            $table->date('tanggal');
            $table->string('plat');
            $table->string('jenis_barang');
            $table->decimal('tonase_kg', 12, 2);
            $table->unsignedBigInteger('harga_per_kg');
            $table->unsignedBigInteger('jumlah');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('invoice_lines');
        Schema::dropIfExists('daily_costs');
        Schema::dropIfExists('trip_items');
        Schema::dropIfExists('trips');
    }
};
