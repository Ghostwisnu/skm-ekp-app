<?php

use App\Models\Client;
use App\Models\Trip;
use App\Models\User;
use App\Models\Vehicle;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

beforeEach(function () {
    $this->actingAs(User::factory()->operator()->create());
});

test('import skips trips with existing no_surat_jalan', function () {
    $client = Client::factory()->create(['nama' => 'Existing Client']);
    $vehicle = Vehicle::factory()->create();
    
    // Create existing trip with specific no_surat_jalan
    $existingTrip = Trip::factory()->create([
        'no_surat_jalan' => 'SJ-20261126-001',
        'client_id' => $client->id,
        'vehicle_id' => $vehicle->id,
        'tanggal' => '2026-11-26',
    ]);

    // Create import with duplicate and unique entries
    $import = new \App\Imports\TripImport();
    $import->previewData = [
        [
            'tanggal' => '2026-11-26',
            'plat' => $vehicle->plat,
            'no_surat_jalan' => 'SJ-20261126-001', // Duplicate
            'category' => 'kontrak',
            'client_name' => 'Client 1',
            'destination_client' => 'Dest Client',
            'jenis_barang' => 'Semen',
            'qty_barang' => 10,
            'total_harga' => 10000,
            'is_auto_generated' => false,
        ],
        [
            'tanggal' => '2026-11-26',
            'plat' => $vehicle->plat,
            'no_surat_jalan' => 'SJ-20261126-002', // Unique
            'category' => 'kontrak',
            'client_name' => 'Client 2',
            'destination_client' => 'Dest Client',
            'jenis_barang' => 'Pupuk',
            'qty_barang' => 5,
            'total_harga' => 5000,
            'is_auto_generated' => false,
        ],
    ];

    $import->store();

    // Should only create 1 new trip (the unique one)
    expect(Trip::count())->toBe(2) // 1 existing + 1 new
        ->and($existingTrip->fresh()->items)->toHaveCount(0); // Existing trip unchanged
});

test('import auto-generates no_surat_jalan for missing entries', function () {
    $client = Client::factory()->create();
    $destinationClient = Client::factory()->create();
    $vehicle = Vehicle::factory()->create();

    $import = new \App\Imports\TripImport();
    $import->previewData = [
        [
            'tanggal' => '2026-11-26',
            'plat' => $vehicle->plat,
            'no_surat_jalan' => '', // Empty - should auto-generate
            'category' => 'kontrak',
            'client_name' => 'Client Test',
            'destination_client' => 'Dest Client',
            'jenis_barang' => 'Semen',
            'qty_barang' => 10,
            'total_harga' => 10000,
            'is_auto_generated' => true,
        ],
    ];

    $import->store();

    $trip = Trip::query()->firstOrFail();
    expect($trip->no_surat_jalan)->toMatch('/^SJ-20261126-\d{3}$/')
        ->and($trip->client->nama)->toBe('Client Test');
});

test('import handles multiple rows with same auto-generated prefix', function () {
    $vehicle = Vehicle::factory()->create();

    $import = new \App\Imports\TripImport();
    $import->previewData = [
        [
            'tanggal' => '2026-11-26',
            'plat' => $vehicle->plat,
            'no_surat_jalan' => '', // Will auto-generate
            'category' => 'kontrak',
            'client_name' => 'Client 1',
            'destination_client' => 'Dest 1',
            'jenis_barang' => 'Semen',
            'qty_barang' => 10,
            'total_harga' => 10000,
            'is_auto_generated' => true,
        ],
        [
            'tanggal' => '2026-11-26',
            'plat' => $vehicle->plat,
            'no_surat_jalan' => '', // Will auto-generate
            'category' => 'kontrak',
            'client_name' => 'Client 2',
            'destination_client' => 'Dest 2',
            'jenis_barang' => 'Pupuk',
            'qty_barang' => 5,
            'total_harga' => 5000,
            'is_auto_generated' => true,
        ],
    ];

    $import->store();

    $trips = Trip::all();
    expect($trips)->toHaveCount(2);
    
    $numbers = $trips->pluck('no_surat_jalan')->sort()->values();
    expect($numbers[0])->toMatch('/^SJ-20261126-\d{3}$/')
        ->and($numbers[1])->toMatch('/^SJ-20261126-\d{3}$/');
});