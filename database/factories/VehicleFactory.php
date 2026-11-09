<?php

namespace Database\Factories;

use App\Models\Vehicle;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Vehicle>
 */
class VehicleFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'plat' => fake()->unique()->bothify('B #### ??'),
            'jenis_kendaraan' => fake()->randomElement(['Truk', 'Tronton', 'Trailer']),
            'model_kendaraan' => fake()->randomElement(['Hino FM', 'Mitsubishi Fuso', 'Isuzu Giga']),
            'nama_supir' => fake()->name(),
            'no_hp_supir' => fake()->numerify('08##########'),
            'keterangan' => null,
        ];
    }
}
