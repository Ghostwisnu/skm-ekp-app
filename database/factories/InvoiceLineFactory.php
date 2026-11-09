<?php

namespace Database\Factories;

use App\Models\Invoice;
use App\Models\InvoiceLine;
use App\Models\Trip;
use App\Models\TripItem;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<InvoiceLine>
 */
class InvoiceLineFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'invoice_id' => Invoice::factory(),
            'trip_id' => Trip::factory(),
            'trip_item_id' => TripItem::factory(),
            'no_surat_jalan' => fake()->bothify('SJ-####'),
            'tanggal' => '2026-11-01',
            'plat' => 'B 1234 XX',
            'jenis_barang' => 'Semen',
            'tonase_kg' => 1000,
            'harga_per_kg' => 1500,
            'jumlah' => 1500000,
        ];
    }
}
