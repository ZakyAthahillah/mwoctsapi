<?php

namespace Database\Factories;

use App\Models\Area;
use App\Models\Shift;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Shift>
 */
class ShiftFactory extends Factory
{
    protected $model = Shift::class;

    public function definition(): array
    {
        return [
            'area_id' => null,
            'name' => 'Shift '.fake()->unique()->numberBetween(1, 999),
            'time_start' => '08:00:00',
            'time_finish' => '17:00:00',
            'status' => 1,
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
