<?php

namespace Database\Factories;

use App\Models\Area;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Area>
 */
class AreaFactory extends Factory
{
    protected $model = Area::class;

    public function definition(): array
    {
        return [
            'code' => strtoupper(fake()->unique()->bothify('AR##??')),
            'name' => fake()->city(),
            'object_name' => fake()->company(),
            'status' => 1,
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 0,
        ]);
    }

    public function deletedStatus(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 99,
        ]);
    }
}
