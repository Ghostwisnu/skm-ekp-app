<?php

use App\Models\Client;
use App\Models\ClientGood;
use App\Models\Trip;
use App\Models\User;
use App\Models\Vehicle;

beforeEach(function () {
    $this->actingAs(User::factory()->operator()->create());
});

function tripPayloadWithoutSj(): array
{
    $client = Client::factory()->create();
    $destinationClient = Client::factory()->create();
    $vehicle = Vehicle::factory()->create();
    // For kontrak category, goods belong to destination client
    $semen = ClientGood::factory()->for($destinationClient)->create([
        'jenis_barang' => 'Semen',
        'pricing_mode' => 'per_qty',
        'harga' => 1000,
    ]);
    $pupuk = ClientGood::factory()->for($destinationClient)->create([
        'jenis_barang' => 'Pupuk',
        'pricing_mode' => 'flat_trip',
        'harga' => 500000,
    ]);

    return [
        'tanggal' => '2026-11-01',
        'plat' => $vehicle->plat,
        'client_id' => $client->id,
        'destination_client_id' => $destinationClient->id,
        'items' => [
            ['client_good_id' => $semen->id, 'jenis_barang' => 'Semen', 'qty_barang' => 5],
            ['client_good_id' => $pupuk->id, 'jenis_barang' => 'Pupuk', 'qty_barang' => 35],
        ],
    ];
}

test('a trip auto-generates no_surat_jalan when field is not provided', function () {
    $payload = tripPayloadWithoutSj();

    $this->post('/trips', $payload)->assertRedirect(route('trips.index'));

    $trip = Trip::query()->firstOrFail();
    expect($trip->no_surat_jalan)->toMatch('/^SJ-\d{8}-\d{3}$/');
});
