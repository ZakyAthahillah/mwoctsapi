<?php

namespace Database\Factories;

use App\Models\Area;
use App\Models\Technician;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Technician>
 */
class TechnicianFactory extends Factory
{
    protected $model = Technician::class;

    public function definition(): array
    {
        return [
            'area_id' => null,
            'code' => strtoupper(fake()->unique()->bothify('TCN###')),
            'name' => fake()->name(),
            'division_id' => null,
            'status' => 1,
            'group_id' => null,
        ];
    }

    public function forArea(?Area $area = null): static
    {
        return $this->state(fn () => [
            'area_id' => ($area ?? Area::factory()->create())->id,
        ]);
    }

    public function deletedStatus(): static
    {
        return $this->state(fn () => [
            'status' => 99,
        ]);
    }
}
