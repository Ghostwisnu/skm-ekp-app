<?php

namespace Database\Factories;

use App\Models\Client;
use App\Models\Trip;
use App\Models\Vehicle;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Trip>
 */
class TripFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'tanggal' => fake()->dateTimeBetween('-30 days')->format('Y-m-d'),
            'vehicle_id' => Vehicle::factory(),
            'client_id' => Client::factory(),
            'destination_client_id' => Client::factory(),
            'sopir' => fake()->firstName(),
            'no_surat_jalan' => fake()->unique()->bothify('SJ-####-####'),
        ];
    }
}
