<?php

use App\Models\DailyCost;
use App\Models\Invoice;
use App\Models\Trip;
use App\Models\TripItem;
use App\Models\User;

test('guests are redirected to the login page', function () {
    $response = $this->get('/dashboard');
    $response->assertRedirect('/login');
});

test('authenticated users can visit the dashboard', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $response = $this->get('/dashboard');
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

    // Create DailyCost manually since there's no trip relationship
    $dc = DailyCost::factory()->create([
        'tanggal' => now()->subMonth()->toDateString(),
        'no_plat' => $trip->vehicle->plat ?? 'B 1234 ABC',
        'no_surat_jalan' => $trip->no_surat_jalan,
        'uang_jalan' => 100,
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

test('dashboard filters by date range', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $today = now()->toDateString();
    $lastMonth = now()->subMonth()->toDateString();

    // Trip hari ini
    $tripToday = Trip::factory()->create([
        'tanggal' => $today,
    ]);
    TripItem::factory()->for($tripToday)->create([
        'qty_barang' => 10,
        'tonase_kg' => 10,
    ]);
    $dcToday = DailyCost::factory()->create([
        'tanggal' => $today,
        'no_plat' => $tripToday->vehicle->plat ?? 'B 1111 AAA',
        'no_surat_jalan' => $tripToday->no_surat_jalan,
        'uang_jalan' => 500,
    ]);

    // Trip bulan lalu
    $tripLastMonth = Trip::factory()->create([
        'tanggal' => $lastMonth,
    ]);
    TripItem::factory()->for($tripLastMonth)->create([
        'qty_barang' => 20,
        'tonase_kg' => 20,
    ]);
    $dcLastMonth = DailyCost::factory()->create([
        'tanggal' => $lastMonth,
        'no_plat' => $tripLastMonth->vehicle->plat ?? 'B 2222 BBB',
        'no_surat_jalan' => $tripLastMonth->no_surat_jalan,
        'uang_jalan' => 300,
    ]);

    // Filter hanya hari ini
    $this->get('/dashboard', ['from' => $today, 'to' => $today])
        ->assertInertia(fn ($page) => $page
            ->component('dashboard')
            ->where('stats.trips', 1)
            ->where('stats.jumlah_barang', 10)
            ->where('stats.biaya', 500)
    );

    // Filter bulan lalu
    $this->get('/dashboard', ['from' => $lastMonth, 'to' => $lastMonth])
        ->assertInertia(fn ($page) => $page
            ->component('dashboard')
            ->where('stats.trips', 1)
            ->where('stats.jumlah_barang', 20)
            ->where('stats.biaya', 300)
    );

    // Filter dari bulan lalu sampai hari ini
    $this->get('/dashboard', ['from' => $lastMonth, 'to' => $today])
        ->assertInertia(fn ($page) => $page
            ->component('dashboard')
            ->where('stats.trips', 2)
            ->where('stats.jumlah_barang', 30)
            ->where('stats.biaya', 800)
    );
});

test('dashboard recent trips filter by date range', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $today = now()->toDateString();
    $lastMonth = now()->subMonth()->toDateString();

    $tripToday = Trip::factory()->create([
        'tanggal' => $today,
    ]);
    $tripLastMonth = Trip::factory()->create([
        'tanggal' => $lastMonth,
    ]);

    // Filter hanya hari ini
    $this->get('/dashboard', ['from' => $today, 'to' => $today])
        ->assertInertia(fn ($page) => $page
            ->where('recentTrips.0.tanggal', $today)
    );

    // Filter bulan lalu
    $this->get('/dashboard', ['from' => $lastMonth, 'to' => $lastMonth])
        ->assertInertia(fn ($page) => $page
            ->where('recentTrips.0.tanggal', $lastMonth)
    );
});