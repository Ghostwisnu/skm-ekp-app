<?php

use App\Models\DailyCost;
use App\Models\Trip;
use App\Models\User;

beforeEach(function () {
    $this->actingAs(User::factory()->operator()->create());
});

test('daily cost total includes extras', function () {
    $cost = DailyCost::factory()->create(['uang_jalan' => 500000]);
    $cost->extras()->create(['nama_biaya' => 'fuel', 'nominal' => 50000]);
    $cost->extras()->create(['nama_biaya' => 'toll', 'nominal' => 30000]);

    expect($cost->fresh()->total)->toBe(580000);
});

test('daily costs are created with required fields', function () {
    $trip = Trip::factory()->create();
    $payload = [
        'tanggal' => now()->toDateString(),
        'no_plat' => 'B 1234',
        'no_surat_jalan' => 'SJ-TEST123',
        'uang_jalan' => 100,
    ];

    $this->post('/daily-costs', $payload)->assertRedirect();
    $this->post('/daily-costs', [
        'tanggal' => now()->toDateString(),
        'no_plat' => 'B 5678',
        'no_surat_jalan' => 'SJ-TEST124',
        'uang_jalan' => 200,
    ])->assertRedirect();

    expect(DailyCost::count())->toBe(2);
});

test('daily cost extras must be non-negative integers', function () {
    $trip = Trip::factory()->create();

    $this->post('/daily-costs', [
        'tanggal' => now()->toDateString(),
        'no_plat' => 'B 1234',
        'no_surat_jalan' => 'SJ-TEST123',
        'uang_jalan' => 100,
        'extras' => [
            ['nama_biaya' => 'pak_bensin', 'nominal' => -1],
        ],
    ])->assertSessionHasErrors('extras.0.nominal');
});

test('a daily cost can be updated', function () {
    $cost = DailyCost::factory()->create(['uang_jalan' => 100]);

    $this->put("/daily-costs/{$cost->id}", [
        'tanggal' => now()->toDateString(),
        'no_plat' => 'B 4321',
        'no_surat_jalan' => 'SJ-UPDATED',
        'uang_jalan' => 500,
    ])->assertRedirect();

    expect($cost->fresh()->uang_jalan)->toBe(500);
});
