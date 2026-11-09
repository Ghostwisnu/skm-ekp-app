<?php

namespace Database\Factories;

use App\Models\DailyCost;
use App\Models\Trip;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<DailyCost>
 */
class DailyCostFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'trip_id' => Trip::factory(),
            'uang_jalan_1' => 500000,
            'uang_bongkar' => 150000,
            'bonus_tepat_waktu' => 50000,
            'bonus_tambahan' => 0,
            'pinjaman' => 100000,
            'uang_jalan_2' => 300000,
            'simpanan' => 50000,
        ];
    }
}
