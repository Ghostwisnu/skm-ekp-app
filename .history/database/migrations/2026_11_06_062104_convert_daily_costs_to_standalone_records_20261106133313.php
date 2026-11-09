<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {

        Schema::create('daily_costs_standalone', function (Blueprint $table): void {
            $table->id();
            $table->date('tanggal')->index();
            $table->string('no_plat', 30)->index();
            $table->string('no_surat_jalan')->unique();
            $table->unsignedBigInteger('uang_jalan')->default(0);
            $table->timestamps();
        });

        $legacyCosts = DB::table('daily_costs')
            ->join('trips', 'daily_costs.trip_id', '=', 'trips.id')
            ->join('vehicles', 'trips.vehicle_id', '=', 'vehicles.id')
            ->select([
                'daily_costs.id',
                'daily_costs.uang_jalan_1',
                'daily_costs.uang_bongkar',
                'daily_costs.bonus_tepat_waktu',
                'daily_costs.bonus_tambahan',
                'daily_costs.pinjaman',
                'daily_costs.uang_jalan_2',
                'daily_costs.simpanan',
                'daily_costs.created_at',
                'daily_costs.updated_at',
                'trips.tanggal',
                'trips.no_surat_jalan',
                'vehicles.plat',
            ])
            ->orderBy('daily_costs.id')
            ->get();

        foreach ($legacyCosts->chunk(200) as $chunk) {
            DB::table('daily_costs_standalone')->insert(
                $chunk->map(fn (object $cost): array => [
                    'id' => $cost->id,
                    'tanggal' => $cost->tanggal,
                    'no_plat' => $cost->plat,
                    'no_surat_jalan' => $cost->no_surat_jalan,
                    'uang_jalan' => (int) $cost->uang_jalan_1
                        + (int) $cost->uang_bongkar
                        + (int) $cost->bonus_tepat_waktu
                        + (int) $cost->bonus_tambahan
                        + (int) $cost->pinjaman
                        + (int) $cost->uang_jalan_2
                        + (int) $cost->simpanan,
                    'created_at' => $cost->created_at,
                    'updated_at' => $cost->updated_at,
                ])->all()
            );
        }

        Schema::drop('daily_costs');
        Schema::rename('daily_costs_standalone', 'daily_costs');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {

        Schema::create('daily_costs_legacy', function (Blueprint $table) {
            $table->id();
            $table->foreignId('trip_id')->nullable()->unique()->constrained()->cascadeOnDelete();
            $table->unsignedBigInteger('uang_jalan_1')->default(0);
            $table->unsignedBigInteger('uang_bongkar')->default(0);
            $table->unsignedBigInteger('bonus_tepat_waktu')->default(0);
            $table->unsignedBigInteger('bonus_tambahan')->default(0);
            $table->unsignedBigInteger('pinjaman')->default(0);
            $table->unsignedBigInteger('uang_jalan_2')->default(0);
            $table->unsignedBigInteger('simpanan')->default(0);
            $table->timestamps();
        });

        $tripIds = DB::table('trips')->pluck('id', 'no_surat_jalan');
        $dailyCosts = DB::table('daily_costs')->orderBy('id')->get();

        foreach ($dailyCosts->chunk(200) as $chunk) {
            DB::table('daily_costs_legacy')->insert(
                $chunk->map(fn (object $cost): array => [
                    'id' => $cost->id,
                    'trip_id' => $tripIds->get($cost->no_surat_jalan),
                    'uang_jalan_1' => $cost->uang_jalan,
                    'uang_bongkar' => 0,
                    'bonus_tepat_waktu' => 0,
                    'bonus_tambahan' => 0,
                    'pinjaman' => 0,
                    'uang_jalan_2' => 0,
                    'simpanan' => 0,
                    'created_at' => $cost->created_at,
                    'updated_at' => $cost->updated_at,
                ])->all()
            );
        }

        Schema::drop('daily_costs');
        Schema::rename('daily_costs_legacy', 'daily_costs');
    }
};
