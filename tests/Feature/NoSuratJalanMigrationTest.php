<?php

use App\Models\Client;
use App\Models\DailyCost;
use App\Models\Trip;
use App\Models\Vehicle;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

test('no surat jalan nullable migration handles SQLite compatibility', function () {
    // Run the migration
    $migration = require database_path('migrations/2026_11_26_000000_modify_no_surat_jalan_nullable_in_trips_table.php');
    $migration->up();

    // Verify trips table still exists and has no_surat_jalan column
    expect(Schema::hasTable('trips'))->toBeTrue()
        ->and(Schema::hasColumn('trips', 'no_surat_jalan'))->toBeTrue();

    // Verify daily_costs table still exists and has no_surat_jalan column
    expect(Schema::hasTable('daily_costs'))->toBeTrue()
        ->and(Schema::hasColumn('daily_costs', 'no_surat_jalan'))->toBeTrue();
});

test('no surat jalan migration creates nullable no_surat_jalan for auto-generation', function () {
    // This test verifies that the migration runs without errors in SQLite
    // and sets up the database for the NoSuratJalanGenerator service to work
    
    $migration = require database_path('migrations/2026_11_26_000000_modify_no_surat_jalan_nullable_in_trips_table.php');
    
    // Run migration up
    $migration->up();
    
    // Verify tables exist
    expect(Schema::hasTable('trips'))->toBeTrue()
        ->and(Schema::hasTable('daily_costs'))->toBeTrue()
        ->and(Schema::hasColumn('trips', 'no_surat_jalan'))->toBeTrue()
        ->and(Schema::hasColumn('daily_costs', 'no_surat_jalan'))->toBeTrue();
    
    // The NoSuratJalanGenerator service auto-generates no_surat_jalan
    // Test that we can create records with auto-generated no_surat_jalan
    $vehicle = Vehicle::factory()->create();
    $client = Client::factory()->create();
    $destinationClient = Client::factory()->create();
    
    // Use the generator service to create a trip with auto-generated no_surat_jalan
    $noSuratJalan = \App\Services\NoSuratJalanGenerator::generate(
        now()->format('Y-m-d'),
        $vehicle->plat
    );
    
    // Create a trip with generated no_surat_jalan
    $trip = Trip::create([
        'tanggal' => now()->toDateString(),
        'vehicle_id' => $vehicle->id,
        'client_id' => $client->id,
        'destination_client_id' => $destinationClient->id,
        'sopir' => 'John Doe',
        'no_surat_jalan' => $noSuratJalan,
    ]);
    
    expect($trip->no_surat_jalan)->toBe($noSuratJalan);
    
    // Create a daily cost with auto-generated no_surat_jalan
    $dailyCostNoSuratJalan = \App\Services\NoSuratJalanGenerator::generateForDailyCost(
        now()->format('Y-m-d'),
        $vehicle->plat
    );
    
    $dailyCost = DailyCost::create([
        'tanggal' => now()->toDateString(),
        'no_plat' => $vehicle->plat,
        'no_surat_jalan' => $dailyCostNoSuratJalan,
        'uang_jalan' => 50000,
    ]);
    
    expect($dailyCost->no_surat_jalan)->toBe($dailyCostNoSuratJalan);
});

test('no surat jalan migration down is safe for SQLite', function () {
    // Run migration up
    $migration = require database_path('migrations/2026_11_26_000000_modify_no_surat_jalan_nullable_in_trips_table.php');
    $migration->up();

    // Run migration down (should not throw in SQLite)
    // For SQLite, down() is a no-op, so it should succeed
    $migration->down();

    // Tables should still exist after down() in SQLite test context
    expect(Schema::hasTable('trips'))->toBeTrue()
        ->and(Schema::hasTable('daily_costs'))->toBeTrue();
});