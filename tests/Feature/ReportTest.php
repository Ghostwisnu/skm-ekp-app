<?php

use App\Models\Client;
use App\Models\DailyCost;
use App\Models\Invoice;
use App\Models\InvoiceLine;
use App\Models\Trip;
use App\Models\TripItem;
use App\Models\User;
use App\Models\Vehicle;
use App\Services\VehiclePerformanceReport;

function reportTrip(Vehicle $vehicle, string $date, float $tonase, int $invoiceValue, ?Client $client = null): Trip
{
    $trip = Trip::factory()->create([
        'vehicle_id' => $vehicle->id, 'tanggal' => $date, ...($client ? ['client_id' => $client->id] : []),
    ]);
    $item = TripItem::factory()->create(['trip_id' => $trip->id, 'tonase_kg' => $tonase]);
    DailyCost::factory()->create(['tanggal' => $date, 'no_plat' => $vehicle->plat, 'no_surat_jalan' => $trip->no_surat_jalan]);
    InvoiceLine::factory()->create([
        'invoice_id' => Invoice::factory()->create(['client_id' => $trip->client_id]),
        'trip_id' => $trip->id, 'trip_item_id' => $item->id, 'jumlah' => $invoiceValue,
    ]);

    return $trip;
}

test('performance per vehicle sums trips, tonnage, invoice value, cost and margin', function () {
    $vehicle = Vehicle::factory()->create(['plat' => 'B 1 A']);
    reportTrip($vehicle, '2026-11-01', 1000, 3000000);
    reportTrip($vehicle, '2026-11-02', 500.5, 2000000);

    $rows = (new VehiclePerformanceReport)->rows();

    expect($rows)->toHaveCount(1)
        ->and($rows[0])->toMatchArray([
            'plat' => 'B 1 A', 'trip' => 2, 'jumlah_barang' => 1500.5,
            'nilai_invoice' => 5000000, 'total_biaya' => 2300000, 'margin' => 2700000,
        ]);
});

test('report can be filtered by period, vehicle and client', function () {
    $a = Vehicle::factory()->create();
    $b = Vehicle::factory()->create();
    $client = Client::factory()->create();
    reportTrip($a, '2026-11-01', 100, 1000, $client);
    reportTrip($a, '2026-12-01', 100, 1000);
    reportTrip($b, '2026-11-01', 100, 1000);

    $report = new VehiclePerformanceReport;

    expect($report->rows(['from' => '2026-11-01', 'to' => '2026-11-30']))->toHaveCount(2)
        ->and($report->rows(['vehicle_id' => $a->id])[0]['trip'])->toBe(2)
        ->and($report->rows(['client_id' => $client->id]))->toHaveCount(1)
        ->and($report->rows(['from' => '2027-01-01']))->toBeEmpty();
});

test('report page and exports work for finance users', function () {
    reportTrip(Vehicle::factory()->create(), '2026-11-01', 100, 1000);
    $this->actingAs(User::factory()->keuangan()->create());

    $this->get('/reports/vehicles')->assertInertia(fn ($page) => $page->component('reports/vehicles')->has('rows', 1));
    $this->get('/reports/vehicles/pdf')->assertOk()->assertHeader('content-type', 'application/pdf');
    $this->get('/reports/vehicles/excel')->assertOk();
});

test('daily costs without trips still appear in report with zero trips', function () {
    $vehicle = Vehicle::factory()->create(['plat' => 'B 1 A']);
    // Create daily cost without a trip
    DailyCost::factory()->create(['no_plat' => 'B 1 A', 'tanggal' => '2026-11-01', 'uang_jalan' => 500000]);

    $rows = (new VehiclePerformanceReport)->rows();

    expect($rows)->toHaveCount(1)
        ->and($rows[0])->toMatchArray([
            'plat' => 'B 1 A',
            'trip' => 0,
            'jumlah_barang' => 0,
            'nilai_invoice' => 0,
            'biaya_uang_jalan' => 500000,
            'biaya_lainnya' => 0,
            'total_biaya' => 500000,
            'margin' => -500000,
        ]);
});
