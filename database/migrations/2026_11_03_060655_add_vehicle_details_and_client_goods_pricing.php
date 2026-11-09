<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('vehicles', function (Blueprint $table) {
            $table->string('jenis_kendaraan')->nullable();
            $table->string('model_kendaraan')->nullable();
            $table->string('nama_supir')->nullable();
            $table->string('no_hp_supir', 30)->nullable();
        });

        Schema::create('client_goods', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_id')->constrained()->cascadeOnDelete();
            $table->string('jenis_barang');
            $table->string('rute');
            $table->string('pricing_mode');
            $table->unsignedBigInteger('harga');
            $table->timestamps();

            $table->index(['client_id', 'jenis_barang']);
        });

        Schema::table('trip_items', function (Blueprint $table) {
            $table->foreignId('client_good_id')->nullable()->constrained()->nullOnDelete();
            $table->string('rute')->nullable();
            $table->decimal('qty_barang', 12, 2)->nullable();
            $table->string('pricing_mode')->nullable();
            $table->unsignedBigInteger('harga_satuan')->nullable();
            $table->unsignedBigInteger('total_harga')->nullable();
        });

        DB::table('trip_items')->whereNull('qty_barang')->update([
            'qty_barang' => DB::raw('tonase_kg'),
        ]);

        Schema::table('invoice_lines', function (Blueprint $table) {
            $table->string('rute')->nullable();
            $table->decimal('qty_barang', 12, 2)->nullable();
            $table->string('pricing_mode')->nullable();
            $table->unsignedBigInteger('harga_satuan')->nullable();
        });

        DB::table('invoice_lines')->whereNull('qty_barang')->update([
            'qty_barang' => DB::raw('tonase_kg'),
        ]);
    }

    public function down(): void
    {
        Schema::table('invoice_lines', function (Blueprint $table) {
            $table->dropColumn(['rute', 'qty_barang', 'pricing_mode', 'harga_satuan']);
        });

        Schema::table('trip_items', function (Blueprint $table) {
            $table->dropForeign(['client_good_id']);
            $table->dropColumn([
                'client_good_id',
                'rute',
                'qty_barang',
                'pricing_mode',
                'harga_satuan',
                'total_harga',
            ]);
        });

        Schema::dropIfExists('client_goods');

        Schema::table('vehicles', function (Blueprint $table) {
            $table->dropColumn(['jenis_kendaraan', 'model_kendaraan', 'nama_supir', 'no_hp_supir']);
        });
    }
};
