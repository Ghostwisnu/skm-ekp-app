<?php

namespace Database\Factories;


use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

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

            'tanggal' => now()->toDateString(),
            'no_plat' => 'B '.fake()->unique()->numerify('#### ??'),
            'no_surat_jalan' => 'SJ-'.Str::upper(Str::random(12)),
            'uang_jalan' => 1_150_000,
        ];
    }
}