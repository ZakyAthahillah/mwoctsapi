<?php

namespace Database\Factories;

use App\Models\Area;
use App\Models\Group;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Group>
 */
class GroupFactory extends Factory
{
    protected $model = Group::class;

    public function definition(): array
    {
        return [
            'area_id' => null,
            'name' => fake()->company(),
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
