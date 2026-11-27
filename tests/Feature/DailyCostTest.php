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

test('a daily cost update auto-generates no_surat_jalan when field is not provided', function () {
    $cost = DailyCost::factory()->create(['uang_jalan' => 100, 'no_plat' => 'B 1234']);

    $this->put("/daily-costs/{$cost->id}", [
        'tanggal' => now()->toDateString(),
        'no_plat' => 'B 1234',
        'no_surat_jalan' => '',
        'uang_jalan' => 500,
    ])->assertRedirect();

    expect($cost->fresh()->uang_jalan)->toBe(500)
        ->and($cost->fresh()->no_surat_jalan)->toMatch('/^DC-\d{8}-\d{3}$/');
});

test('daily cost auto-generates no_surat_jalan when not provided', function () {
    $payload = [
        'tanggal' => now()->toDateString(),
        'no_plat' => 'B 1234',
        'no_surat_jalan' => null,
        'uang_jalan' => 100,
    ];

    $this->post('/daily-costs', $payload)->assertRedirect();
    $cost = DailyCost::query()->firstOrFail();
    expect($cost->no_surat_jalan)->toMatch('/^DC-\d{8}-\d{3}$/');
});

test('daily cost auto-generates no_surat_jalan when empty string provided', function () {
    $payload = [
        'tanggal' => now()->toDateString(),
        'no_plat' => 'B 5678',
        'no_surat_jalan' => '',
        'uang_jalan' => 200,
    ];

    $this->post('/daily-costs', $payload)->assertRedirect();
    $cost = DailyCost::where('no_plat', 'B 5678')->firstOrFail();
    expect($cost->no_surat_jalan)->toMatch('/^DC-\d{8}-\d{3}$/');
});
