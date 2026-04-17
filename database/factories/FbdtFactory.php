<?php

namespace Database\Factories;

use App\Models\Area;
use App\Models\Fbdt;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Fbdt>
 */
class FbdtFactory extends Factory
{
    protected $model = Fbdt::class;

    public function definition(): array
    {
        return [
            'area_id' => Area::factory(),
            'tahun' => fake()->numberBetween(2024, 2030),
            'bulan' => fake()->numberBetween(1, 12),
            'fb' => fake()->randomFloat(2, 0, 100),
            'dt' => fake()->randomFloat(2, 0, 100),
            'mtbf' => fake()->randomFloat(2, 0, 100),
            'mttr' => fake()->randomFloat(2, 0, 100),
        ];
    }
}
