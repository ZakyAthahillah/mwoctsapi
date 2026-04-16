<?php

namespace Database\Factories;

use App\Models\Area;
use App\Models\Informant;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Informant>
 */
class InformantFactory extends Factory
{
    protected $model = Informant::class;

    public function definition(): array
    {
        return [
            'area_id' => null,
            'code' => strtoupper(fake()->unique()->bothify('IF##??')),
            'name' => fake()->name(),
            'status' => 1,
            'group_id' => null,
        ];
    }

    public function forArea(?Area $area = null): static
    {
        return $this->state(fn (array $attributes) => [
            'area_id' => $area?->id ?? Area::factory(),
        ]);
    }

    public function deletedStatus(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 99,
        ]);
    }
}
