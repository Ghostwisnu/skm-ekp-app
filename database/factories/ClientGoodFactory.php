<?php

namespace Database\Factories;

use App\Models\Client;
use App\Models\ClientGood;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ClientGood>
 */
class ClientGoodFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'client_id' => Client::factory(),
            'jenis_barang' => fake()->randomElement(['Semen', 'Pupuk', 'Beras']),
            'pricing_mode' => 'per_qty',
            'harga' => fake()->numberBetween(1000, 100000),
        ];
    }
}
