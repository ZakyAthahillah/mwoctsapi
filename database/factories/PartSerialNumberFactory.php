<?php

namespace Database\Factories;

use App\Models\Area;
use App\Models\Part;
use App\Models\PartSerialNumber;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PartSerialNumber>
 */
class PartSerialNumberFactory extends Factory
{
    protected $model = PartSerialNumber::class;

    public function definition(): array
    {
        return [
            'area_id' => null,
            'part_id' => null,
            'serial_number' => strtoupper(fake()->unique()->bothify('SN###??')),
            'status' => 1,
        ];
    }

    public function forArea(?Area $area = null): static
    {
        return $this->state(fn () => [
            'area_id' => ($area ?? Area::factory()->create())->id,
        ]);
    }

    public function forPart(?Part $part = null): static
    {
        return $this->state(fn () => [
            'part_id' => ($part ?? Part::factory()->create())->id,
        ]);
    }

    public function active(): static
    {
        return $this->state(fn () => [
            'status' => 1,
        ]);
    }

    public function inactive(): static
    {
        return $this->state(fn () => [
            'status' => 0,
        ]);
    }
}
