<?php

use App\Models\Client;
use App\Models\ClientGood;
use App\Models\Invoice;
use App\Models\Trip;
use App\Models\User;
use App\Models\Vehicle;

beforeEach(function () {
    $this->actingAs(User::factory()->operator()->create());
});

function tripPayload(array $overrides = []): array
{
    $client = Client::factory()->create();
    $destinationClient = Client::factory()->create();
    $vehicle = Vehicle::factory()->create();
    $semen = ClientGood::factory()->for($client)->create([
        'jenis_barang' => 'Semen',
        'pricing_mode' => 'per_qty',
        'harga' => 1000,
    ]);
    $pupuk = ClientGood::factory()->for($client)->create([
        'jenis_barang' => 'Pupuk',
        'pricing_mode' => 'flat_trip',
        'harga' => 500000,
    ]);

    return array_merge([
        'tanggal' => '2026-11-01',
        'plat' => $vehicle->plat,
        'client_id' => $client->id,
        'destination_client_id' => $destinationClient->id,
        'no_surat_jalan' => 'SJ-2611-0012',
        'items' => [
            ['client_good_id' => $semen->id, 'jenis_barang' => 'Semen', 'qty_barang' => 5],
            ['client_good_id' => $pupuk->id, 'jenis_barang' => 'Pupuk', 'qty_barang' => 35],
        ],
    ], $overrides);
}

test('a trip snapshots client pricing and calculates per quantity and flat prices', function () {
    $this->post('/trips', tripPayload())->assertRedirect(route('trips.index'));

    $trip = Trip::first();
    expect($trip->no_surat_jalan)->toBe('SJ-2611-0012')
        ->and($trip->items)->toHaveCount(2)
        ->and($trip->items[0]->jenis_barang)->toBe('Semen')
        ->and($trip->items[0]->rute)->toBe($trip->client->nama.' → '.$trip->destinationClient->nama)
        ->and($trip->items[0]->total_harga)->toBe(5000)
        ->and($trip->items[1]->total_harga)->toBe(500000)
        ->and($trip->sopir)->toBe($trip->vehicle->nama_supir);
});

test('a trip stores a manually entered item total instead of the configured calculation', function () {
    $payload = tripPayload();
    $payload['items'][0]['total_harga'] = 12345;

    $this->post('/trips', $payload)->assertRedirect(route('trips.index'));

    $trip = Trip::query()->firstOrFail();
    expect($trip->items[0]->total_harga)->toBe(12345)
        ->and($trip->items[1]->total_harga)->toBe(500000);
});

test('a trip creates a vehicle and client good when their values are entered manually', function () {
    $payload = tripPayload([
        'plat' => 'B 1234 MAN',
        'items' => [
            ['client_good_id' => null, 'jenis_barang' => 'Barang Baru', 'qty_barang' => 8],
            ['client_good_id' => null, 'jenis_barang' => 'Barang Lain', 'qty_barang' => 3],
        ],
    ]);

    $this->post('/trips', $payload)->assertRedirect(route('trips.index'));

    $trip = Trip::query()->firstOrFail();
    $good = ClientGood::query()
        ->where('client_id', $trip->client_id)
        ->where('jenis_barang', 'Barang Baru')
        ->firstOrFail();
    $otherGood = ClientGood::query()
        ->where('client_id', $trip->client_id)
        ->where('jenis_barang', 'Barang Lain')
        ->firstOrFail();

    expect($trip->vehicle->plat)->toBe('B 1234 MAN')
        ->and($good->pricing_mode)->toBe('per_qty')
        ->and($good->harga)->toBe(0)
        ->and($trip->items->first()->client_good_id)->toBe($good->id)
        ->and($trip->items->first()->total_harga)->toBe(0)
        ->and($otherGood->harga)->toBe(0);
});

test('a trip creates origin and destination clients when their names are entered manually', function () {
    $payload = tripPayload([
        'client_id' => null,
        'client_name' => 'Client Asal Baru',
        'destination_client_id' => null,
        'destination_client_name' => 'Client Tujuan Baru',
        'items' => [
            ['client_good_id' => null, 'jenis_barang' => 'Barang Baru', 'qty_barang' => 8],
        ],
    ]);

    $this->post('/trips', $payload)->assertRedirect(route('trips.index'));

    $trip = Trip::query()->firstOrFail();

    expect($trip->client->nama)->toBe('Client Asal Baru')
        ->and($trip->destinationClient->nama)->toBe('Client Tujuan Baru')
        ->and($trip->items->first()->rute)->toBe('Client Asal Baru → Client Tujuan Baru');
});

test('trip validation requires unique surat jalan and at least one item', function () {
    Trip::factory()->create(['no_surat_jalan' => 'SJ-DUP']);

    $this->post('/trips', tripPayload(['no_surat_jalan' => 'SJ-DUP', 'items' => []]))
        ->assertSessionHasErrors(['no_surat_jalan', 'items']);
});

test('a trip cannot use a good configured for another client', function () {
    $payload = tripPayload();
    $otherClient = Client::factory()->create();
    $otherGood = ClientGood::factory()->for($otherClient)->create();
    $payload['items'][0]['client_good_id'] = $otherGood->id;

    $this->post('/trips', $payload)
        ->assertSessionHasErrors('items.0.client_good_id');

    expect(Trip::count())->toBe(0);
});

test('a trip can be updated and its items replaced', function () {
    $this->post('/trips', tripPayload());
    $trip = Trip::first();
    $newGood = ClientGood::factory()->for($trip->client)->create([
        'jenis_barang' => 'Beras',
        'harga' => 2000,
    ]);

    $this->put("/trips/{$trip->id}", [
        'tanggal' => '2026-11-01',
        'plat' => $trip->vehicle->plat,
        'client_id' => $trip->client_id,
        'destination_client_id' => $trip->destination_client_id,
        'no_surat_jalan' => 'SJ-2611-0012',
        'items' => [[
            'client_good_id' => $newGood->id,
            'jenis_barang' => 'Beras',
            'qty_barang' => 10,
        ]],
    ])->assertRedirect(route('trips.index'));

    expect($trip->fresh()->items)->toHaveCount(1)
        ->and($trip->fresh()->items->first()->total_harga)->toBe(20000);
});

test('a trip requires two different client endpoints', function () {
    $payload = tripPayload();
    $payload['destination_client_id'] = $payload['client_id'];

    $this->post('/trips', $payload)
        ->assertSessionHasErrors('destination_client_id');

    expect(Trip::query()->count())->toBe(0);
});

test('a trip rejects the same manually entered client for both endpoints', function () {
    $payload = tripPayload([
        'client_id' => null,
        'client_name' => 'Client Baru',
        'destination_client_id' => null,
        'destination_client_name' => ' client baru ',
    ]);

    $this->post('/trips', $payload)
        ->assertSessionHasErrors('destination_client_name');

    expect(Trip::query()->count())->toBe(0);
});

test('an invoiced trip cannot be changed or deleted', function () {
    $trip = Trip::factory()->create(['invoice_id' => Invoice::factory()->create()->id]);

    $this->delete("/trips/{$trip->id}")->assertRedirect(route('trips.index'));
    $this->assertModelExists($trip);
});

test('the trip list can be filtered by vehicle', function () {
    $trip = Trip::factory()->create();
    Trip::factory()->create();

    $this->get('/trips?vehicle_id='.$trip->vehicle_id)
        ->assertInertia(fn ($page) => $page->component('trips/index')->has('trips.data', 1));
});
