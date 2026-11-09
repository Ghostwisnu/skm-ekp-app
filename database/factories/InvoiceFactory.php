<?php

namespace Database\Factories;

use App\Models\Client;
use App\Models\Invoice;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Invoice>
 */
class InvoiceFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'no_invoice' => fake()->unique()->bothify('INV/2026/11/###'),
            'client_id' => Client::factory(),
            'destination_client_id' => Client::factory(),
            'tanggal_awal' => '2026-11-01',
            'tanggal_akhir' => '2026-11-30',
            'ppn_persen' => 11,
            'subtotal' => 0,
            'ppn_nominal' => 0,
            'total' => 0,
            'status' => Invoice::STATUS_DRAFT,
        ];
    }

    public function final(): static
    {
        return $this->state(fn () => ['status' => Invoice::STATUS_FINAL]);
    }
}
