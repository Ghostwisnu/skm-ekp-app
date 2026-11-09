<?php

use App\Models\Client;
use App\Models\ClientGood;
use App\Models\Trip;
use App\Models\User;
use App\Models\Vehicle;

beforeEach(function () {
    $this->actingAs(User::factory()->operator()->create());
});

test('clients can be created updated and deleted', function () {
    $goods = [[
        'jenis_barang' => 'Semen',
        'pricing_mode' => 'per_qty',
        'harga' => 1000,
    ]];
    $this->post('/clients', ['nama' => 'PT Sinar Makmur', 'goods' => $goods])->assertRedirect();
    $client = Client::firstOrFail();
    expect($client->alamat)->toBeNull()
        ->and($client->goods)->toHaveCount(1);

    $this->put("/clients/{$client->id}", [
        'nama' => 'PT Baru',
        'goods' => [[
            'id' => $client->goods->first()->id,
            'jenis_barang' => 'Semen',
            'pricing_mode' => 'per_qty',
            'harga' => 1500,
        ]],
    ])->assertRedirect();
    expect($client->fresh()->nama)->toBe('PT Baru');

    $this->delete("/clients/{$client->id}")->assertRedirect();
    $this->assertModelMissing($client);
});

test('a client can be saved without configured goods but requires a name', function () {
    $this->post('/clients', ['nama' => 'PT Tanpa Barang'])
        ->assertRedirect();

    $client = Client::query()->where('nama', 'PT Tanpa Barang')->firstOrFail();
    expect($client->goods)->toBeEmpty();

    $this->post('/clients', ['goods' => []])->assertSessionHasErrors('nama');
});

test('clients can configure goods with per quantity or flat trip pricing', function () {
    $this->post('/clients', [
        'nama' => 'PT Tarif',
        'goods' => [
            [
                'jenis_barang' => 'Semen',
                'pricing_mode' => 'per_qty',
                'harga' => 2500,
            ],
            [
                'jenis_barang' => 'Pupuk',
                'pricing_mode' => 'flat_trip',
                'harga' => 750000,
            ],
        ],
    ])->assertRedirect();

    $client = Client::query()->where('nama', 'PT Tarif')->firstOrFail();
    expect($client->goods)->toHaveCount(2)
        ->and($client->goods->firstWhere('jenis_barang', 'Semen')->harga)->toBe(2500)
        ->and($client->goods->firstWhere('jenis_barang', 'Pupuk')->pricing_mode)->toBe('flat_trip');

    $semen = $client->goods->firstWhere('jenis_barang', 'Semen');
    $this->put("/clients/{$client->id}", [
        'nama' => $client->nama,
        'alamat' => $client->alamat,
        'goods' => [[
            'id' => $semen->id,
            'jenis_barang' => 'Semen',
            'pricing_mode' => 'per_qty',
            'harga' => 3000,
        ]],
    ])->assertRedirect();

    expect(ClientGood::query()->where('client_id', $client->id)->count())->toBe(1)
        ->and($semen->fresh()->harga)->toBe(3000);
});

test('a client used by a trip is not deleted', function () {
    $trip = Trip::factory()->create();

    $this->delete("/clients/{$trip->client_id}")->assertRedirect();
    $this->assertModelExists($trip->client);
});

test('vehicle plates must be unique', function () {
    Vehicle::factory()->create(['plat' => 'B 9123 KXA']);

    $payload = [
        'jenis_kendaraan' => 'Tronton',
        'model_kendaraan' => 'Hino FM',
        'nama_supir' => 'Slamet',
        'no_hp_supir' => '081234567890',
    ];
    $this->post('/vehicles', ['plat' => 'B 9123 KXA', ...$payload])->assertSessionHasErrors('plat');
    $this->post('/vehicles', ['plat' => 'D 8841 AB', ...$payload])->assertSessionDoesntHaveErrors();
    expect(Vehicle::count())->toBe(2);
    expect(Vehicle::query()->where('plat', 'D 8841 AB')->firstOrFail()->only([
        'jenis_kendaraan',
        'model_kendaraan',
        'nama_supir',
        'no_hp_supir',
    ]))->toMatchArray($payload);
});
