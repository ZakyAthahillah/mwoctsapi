<?php

namespace Database\Factories;

use App\Models\Area;
use App\Models\Operation;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Operation>
 */
class OperationFactory extends Factory
{
    protected $model = Operation::class;

    public function definition(): array
    {
        return [
            'area_id' => null,
            'code' => strtoupper(fake()->unique()->bothify('OPR###')),
            'name' => fake()->sentence(2),
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
