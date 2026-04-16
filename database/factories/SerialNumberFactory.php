<?php

namespace Database\Factories;

use App\Models\Area;
use App\Models\Machine;
use App\Models\Part;
use App\Models\PartSerialNumber;
use App\Models\Position;
use App\Models\SerialNumber;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SerialNumber>
 */
class SerialNumberFactory extends Factory
{
    protected $model = SerialNumber::class;

    public function definition(): array
    {
        return [
            'area_id' => Area::factory(),
            'machine_id' => Machine::factory(),
            'position_id' => Position::factory(),
            'part_id' => Part::factory(),
            'part_serial_number_id' => PartSerialNumber::factory(),
        ];
    }
}
