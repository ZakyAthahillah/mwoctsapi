<?php

namespace Database\Factories;

use App\Models\Area;
use App\Models\Machine;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Machine>
 */
class MachineFactory extends Factory
{
    protected $model = Machine::class;

    public function definition(): array
    {
        return [
            'area_id' => null,
            'code' => strtoupper(fake()->unique()->bothify('MC###??')),
            'name' => fake()->company(),
            'description' => fake()->sentence(),
            'image' => null,
            'image_side' => null,
            'status' => 0,
        ];
    }

    public function forArea(?Area $area = null): static
    {
        return $this->state(fn (array $attributes) => [
            'area_id' => $area?->id ?? Area::factory(),
        ]);
    }

    public function active(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 1,
        ]);
    }

    public function deletedStatus(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 99,
        ]);
    }
}
