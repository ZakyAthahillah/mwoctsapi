<?php

namespace Database\Factories;

use App\Models\Area;
use App\Models\Reason;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Reason>
 */
class ReasonFactory extends Factory
{
    protected $model = Reason::class;

    public function definition(): array
    {
        return [
            'area_id' => null,
            'code' => strtoupper(fake()->unique()->bothify('RSN###')),
            'name' => fake()->sentence(2),
            'division_id' => null,
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
