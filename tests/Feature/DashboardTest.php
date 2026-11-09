<?php

use App\Models\DailyCost;
use App\Models\Invoice;
use App\Models\Trip;
use App\Models\TripItem;
use App\Models\User;

test('guests are redirected to the login page', function () {
    $response = $this->get(route('dashboard'));
    $response->assertRedirect(route('login'));
});

test('authenticated users can visit the dashboard', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $response = $this->get(route('dashboard'));
    $response->assertOk();
});

test('dashboard statistics include trips and invoices from all dates', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $trip = Trip::factory()->create([
        'tanggal' => now()->subMonth()->toDateString(),
    ]);

    TripItem::factory()->for($trip)->create([
        'qty_barang' => 12,
        'tonase_kg' => 12,
    ]);

    DailyCost::factory()->for($trip)->create([
        'uang_jalan_1' => 100,
        'uang_bongkar' => 0,
        'bonus_tepat_waktu' => 0,
        'bonus_tambahan' => 0,
        'pinjaman' => 0,
        'uang_jalan_2' => 0,
        'simpanan' => 0,
    ]);

    Invoice::factory()->create([
        'tanggal_awal' => now()->subMonth()->startOfMonth()->toDateString(),
        'tanggal_akhir' => now()->subMonth()->endOfMonth()->toDateString(),
        'total' => 2500,
    ]);

    $this->get(route('dashboard'))
        ->assertInertia(fn ($page) => $page
            ->component('dashboard')
            ->where('periode', 'Semua data')
            ->where('stats.trips', 1)
            ->where('stats.jumlah_barang', 12)
            ->where('stats.biaya', 100)
            ->where('stats.invoice_count', 1)
            ->where('stats.invoice_total', 2500));
});
