<?php

use App\Models\Client;
use App\Models\ClientGood;
use App\Models\Invoice;
use App\Models\Setting;
use App\Models\Trip;
use App\Models\TripItem;
use App\Models\User;
use App\Models\Vehicle;

beforeEach(function () {
    $this->actingAs(User::factory()->keuangan()->create());
    $this->client = Client::factory()->create();
});

function tripWithItem(Client $client, string $date, float $tonase, array $trip = []): TripItem
{
    $created = Trip::factory()->create(['client_id' => $client->id, 'tanggal' => $date, ...$trip]);

    return TripItem::factory()->create(['trip_id' => $created->id, 'tonase_kg' => $tonase]);
}

test('create page lists pending items of the client within the period', function () {
    $destination = Client::factory()->create();
    tripWithItem($this->client, '2026-11-02', 1000, ['destination_client_id' => $destination->id]);
    tripWithItem($this->client, '2026-12-02', 1000);
    tripWithItem($this->client, '2026-11-02', 1000);
    tripWithItem(Client::factory()->create(), '2026-11-02', 1000);

    $this->get('/invoices/create?'.http_build_query([
        'client_id' => $this->client->id,
        'destination_client_id' => $destination->id,
        'tanggal_awal' => '2026-11-01',
        'tanggal_akhir' => '2026-11-30',
    ]))->assertInertia(fn ($page) => $page
        ->component('invoices/create')
        ->has('pending', 1)
        ->where('filters.destination_client_id', (string) $destination->id));
});

test('a newly saved trip appears in invoice pending rows for its source client and date', function () {
    $destination = Client::factory()->create();
    $vehicle = Vehicle::factory()->create();
    $good = ClientGood::factory()->for($this->client)->create([
        'jenis_barang' => 'Barang Invoice',
        'pricing_mode' => 'per_qty',
        'harga' => 2500,
    ]);

    $this->actingAs(User::factory()->operator()->create())
        ->post('/trips', [
            'tanggal' => '2026-11-02',
            'plat' => $vehicle->plat,
            'client_id' => $this->client->id,
            'destination_client_id' => $destination->id,
            'no_surat_jalan' => 'SJ-INVOICE-PENDING',
            'items' => [[
                'client_good_id' => $good->id,
                'jenis_barang' => 'Barang Invoice',
                'qty_barang' => 4,
            ]],
        ])
        ->assertRedirect(route('trips.index'));

    $this->actingAs(User::factory()->keuangan()->create())
        ->get('/invoices/create?'.http_build_query([
            'client_id' => $this->client->id,
            'destination_client_id' => $destination->id,
            'tanggal_awal' => '2026-11-01',
            'tanggal_akhir' => '2026-11-30',
        ]))
        ->assertInertia(fn ($page) => $page
            ->component('invoices/create')
            ->has('pending', 1));
});

test('an invoice is built with subtotal, vat and total', function () {
    $a = tripWithItem($this->client, '2026-11-01', 8500);
    $destinationClientId = $a->trip->destination_client_id;
    $b = tripWithItem($this->client, '2026-11-02', 1000.5, [
        'destination_client_id' => $destinationClientId,
    ]);
    $otherDestinationItem = tripWithItem($this->client, '2026-11-03', 500, [
        'destination_client_id' => Client::factory()->create()->id,
    ]);

    $this->post('/invoices', [
        'client_id' => $this->client->id,
        'destination_client_id' => $destinationClientId,
        'tanggal_awal' => '2026-11-01',
        'tanggal_akhir' => '2026-11-30',
        'no_invoice' => 'INV/2026/11/001',
        'ppn_persen' => 11,
        'prices' => [$a->id => 1000, $b->id => 2000],
    ])->assertRedirect();

    $invoice = Invoice::with('lines')->firstOrFail();
    expect($invoice->lines)->toHaveCount(2)
        ->and($invoice->destination_client_id)->toBe($destinationClientId)
        ->and($invoice->subtotal)->toBe(8500000 + 2001000)
        ->and($invoice->ppn_nominal)->toBe((int) round(10501000 * 0.11))
        ->and($invoice->total)->toBe($invoice->subtotal + $invoice->ppn_nominal)
        ->and($invoice->status)->toBe('draft')
        ->and($a->trip->fresh()->invoice_id)->toBe($invoice->id)
        ->and($otherDestinationItem->trip->fresh()->invoice_id)->toBeNull();
});

test('an invoice uses the client goods total saved on the trip without manual prices', function () {
    $good = ClientGood::factory()->for($this->client)->create([
        'jenis_barang' => 'Semen',
        'pricing_mode' => 'per_qty',
        'harga' => 2500,
    ]);
    $trip = Trip::factory()->create(['client_id' => $this->client->id]);
    $item = TripItem::factory()->create([
        'trip_id' => $trip->id,
        'client_good_id' => $good->id,
        'jenis_barang' => $good->jenis_barang,
        'rute' => "{$this->client->nama} → {$trip->destinationClient->nama}",
        'pricing_mode' => $good->pricing_mode,
        'qty_barang' => 4,
        'harga_satuan' => $good->harga,
        'total_harga' => 10000,
    ]);

    $this->post('/invoices', [
        'client_id' => $this->client->id,
        'destination_client_id' => $trip->destination_client_id,
        'tanggal_awal' => $trip->tanggal->format('Y-m-d'),
        'tanggal_akhir' => $trip->tanggal->format('Y-m-d'),
        'no_invoice' => 'INV-CLIENT-RATE',
        'ppn_persen' => 0,
    ])->assertRedirect();

    $invoice = Invoice::with('lines')->firstOrFail();
    expect($invoice->subtotal)->toBe(10000)
        ->and($invoice->lines->first()->rute)->toBe("{$this->client->nama} → {$trip->destinationClient->nama}")
        ->and($invoice->lines->first()->qty_barang)->toBe(4.0)
        ->and($item->trip->fresh()->invoice_id)->toBe($invoice->id);
});

test('invoice preserves company details and uses the period end as the document date', function () {
    $item = tripWithItem($this->client, '2026-11-02', 100);
    $companyDetails = [
        'company_name' => 'Ekspedisi Pemilik',
        'company_address' => 'Jl. Merdeka No. 10',
        'company_bank_name' => 'Bank Nusantara',
        'company_bank_account' => '1234567890',
    ];

    $this->actingAs(User::factory()->admin()->create())
        ->put('/admin/settings', $companyDetails)
        ->assertRedirect();

    $this->actingAs(User::factory()->keuangan()->create())
        ->post('/invoices', [
            'client_id' => $this->client->id,
            'destination_client_id' => $item->trip->destination_client_id,
            'tanggal_awal' => '2026-11-01',
            'tanggal_akhir' => '2026-11-30',
            'no_invoice' => 'INV-COMPANY-SNAPSHOT',
            'ppn_persen' => 0,
            'prices' => [$item->id => 1000],
        ])
        ->assertRedirect();

    $invoice = Invoice::query()->firstOrFail();
    expect($invoice->company_name)->toBe('Ekspedisi Pemilik')
        ->and($invoice->company_address)->toBe('Jl. Merdeka No. 10')
        ->and($invoice->company_bank_name)->toBe('Bank Nusantara')
        ->and($invoice->company_bank_account)->toBe('1234567890')
        ->and($invoice->tanggal_akhir->format('Y-m-d'))->toBe('2026-11-30');

    $this->actingAs(User::factory()->admin()->create())
        ->put('/admin/settings', ['company_name' => 'Nama Baru'])
        ->assertRedirect();

    $this->actingAs(User::factory()->keuangan()->create())
        ->get("/invoices/{$invoice->id}")
        ->assertInertia(fn ($page) => $page
            ->component('invoices/show')
            ->where('company.name', 'Ekspedisi Pemilik')
            ->where('company.bank_name', 'Bank Nusantara')
            ->where('invoice.destination_client.nama', $invoice->destinationClient->nama)
            ->where('invoice.tanggal_akhir', '2026-11-30'));

    $html = view('pdf.invoice', [
        'invoice' => $invoice->load(['client', 'destinationClient', 'lines']),
        'company' => [
            'name' => $invoice->company_name,
            'address' => $invoice->company_address,
            'bank_name' => $invoice->company_bank_name,
            'bank_account' => $invoice->company_bank_account,
        ],
    ])->render();
    $footerPosition = strpos($html, '<table class="footer">');

    expect($html)->toContain('INVOICE - '.e($invoice->destinationClient->nama))
        ->toContain('PERIODE 01/11/2026 - 30/11/2026')
        ->toContain('30 November 2026')
        ->toContain('Kwitansi')
        ->toContain('Attention To:')
        ->toContain('<div class="attention-name">'.e($invoice->client->nama).'</div>')
        ->toContain('Jl. Merdeka No. 10')
        ->toContain('<div class="signature-name">Ekspedisi Pemilik</div>')
        ->toContain('TGL')
        ->toContain('NO. POLISI')
        ->toContain('UKURAN')
        ->toContain('HARGA @')
        ->toContain('SUB TOTAL')
        ->toContain('GRAND TOTAL')
        ->toContain('Bank Nusantara')
        ->toContain('1234567890')
        ->toContain(e($invoice->destinationClient->nama));

    expect(strpos($html, 'Jl. Merdeka No. 10'))->toBeLessThan($footerPosition)
        ->and(substr($html, $footerPosition))->not->toContain('Jl. Merdeka No. 10');
});

test('ppn may be zero', function () {
    $a = tripWithItem($this->client, '2026-11-01', 100);

    $this->post('/invoices', [
        'client_id' => $this->client->id, 'destination_client_id' => $a->trip->destination_client_id,
        'tanggal_awal' => '2026-11-01', 'tanggal_akhir' => '2026-11-01',
        'no_invoice' => 'INV-0', 'ppn_persen' => 0, 'prices' => [$a->id => 10],
    ]);

    expect(Invoice::first()->total)->toBe(1000);
});

test('already invoiced trips are not invoiced twice', function () {
    $a = tripWithItem($this->client, '2026-11-01', 100);
    $payload = fn (string $no) => [
        'client_id' => $this->client->id, 'destination_client_id' => $a->trip->destination_client_id,
        'tanggal_awal' => '2026-11-01', 'tanggal_akhir' => '2026-11-30',
        'no_invoice' => $no, 'ppn_persen' => 11, 'prices' => [$a->id => 10],
    ];

    $this->post('/invoices', $payload('INV-1'))->assertRedirect();
    $this->post('/invoices', $payload('INV-2'))->assertSessionHasErrors('tanggal_awal');

    expect(Invoice::count())->toBe(1);
});

test('every pending row needs a price and the invoice number must be unique', function () {
    $a = tripWithItem($this->client, '2026-11-01', 100);
    $base = [
        'client_id' => $this->client->id, 'destination_client_id' => $a->trip->destination_client_id,
        'tanggal_awal' => '2026-11-01', 'tanggal_akhir' => '2026-11-30',
        'no_invoice' => 'INV-X', 'ppn_persen' => 11,
    ];

    $this->post('/invoices', [...$base, 'prices' => []])->assertSessionHasErrors();
    expect(Invoice::count())->toBe(0);

    Invoice::factory()->create(['no_invoice' => 'INV-X']);
    $this->post('/invoices', [...$base, 'prices' => [$a->id => 5]])->assertSessionHasErrors('no_invoice');
});

test('a draft invoice can be finalized and a final one cannot be deleted', function () {
    $invoice = Invoice::factory()->create();

    $this->post("/invoices/{$invoice->id}/finalize")->assertRedirect();
    expect($invoice->fresh()->status)->toBe('final');

    $this->delete("/invoices/{$invoice->id}")->assertRedirect();
    $this->assertModelExists($invoice);
});

test('deleting a draft invoice releases its trips', function () {
    $a = tripWithItem($this->client, '2026-11-01', 100);
    $this->post('/invoices', [
        'client_id' => $this->client->id, 'destination_client_id' => $a->trip->destination_client_id,
        'tanggal_awal' => '2026-11-01', 'tanggal_akhir' => '2026-11-30',
        'no_invoice' => 'INV-D', 'ppn_persen' => 11, 'prices' => [$a->id => 10],
    ]);

    $this->delete('/invoices/'.Invoice::first()->id)->assertRedirect(route('invoices.index'));

    expect($a->trip->fresh()->invoice_id)->toBeNull();
});

test('invoice detail, pdf and excel are available', function () {
    $a = tripWithItem($this->client, '2026-11-01', 100);
    $this->post('/invoices', [
        'client_id' => $this->client->id, 'destination_client_id' => $a->trip->destination_client_id,
        'tanggal_awal' => '2026-11-01', 'tanggal_akhir' => '2026-11-30',
        'no_invoice' => 'INV/PDF', 'ppn_persen' => 11, 'prices' => [$a->id => 10],
    ]);
    $invoice = Invoice::first();

    $this->get("/invoices/{$invoice->id}")->assertOk();
    $this->get('/invoices')->assertInertia(fn ($page) => $page
        ->component('invoices/index')
        ->where('invoices.data.0.destination_client.nama', $invoice->destinationClient->nama));
    $this->get("/invoices/{$invoice->id}/pdf")->assertOk()->assertHeader('content-type', 'application/pdf');
    $this->get("/invoices/{$invoice->id}/excel")->assertOk();
});

test('invoice requires distinct source and destination clients', function () {
    $payload = [
        'client_id' => $this->client->id,
        'destination_client_id' => $this->client->id,
        'tanggal_awal' => '2026-11-01',
        'tanggal_akhir' => '2026-11-30',
        'no_invoice' => 'INV-SAME-CLIENT',
        'ppn_persen' => 0,
    ];

    $this->post('/invoices', $payload)
        ->assertSessionHasErrors('destination_client_id');

    expect(Invoice::query()->count())->toBe(0);
});

test('default ppn comes from settings', function () {
    expect(Setting::ppnDefault())->toBe(11.0);

    $this->actingAs(User::factory()->admin()->create());
    $this->put('/admin/settings', ['ppn_default' => 10])->assertRedirect();

    expect(Setting::ppnDefault())->toBe(10.0);
});
