<?php

namespace Database\Factories;

use App\Models\Trip;
use App\Models\TripItem;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TripItem>
 */
class TripItemFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'trip_id' => Trip::factory(),
            'jenis_barang' => fake()->randomElement(['Semen', 'Pupuk', 'Beras', 'Besi']),
            'tonase_kg' => fake()->randomFloat(2, 1000, 12000),
        ];
    }
}
