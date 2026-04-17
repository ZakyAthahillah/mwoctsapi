<?php

namespace Tests\Feature;

use App\Models\Area;
use App\Models\Division;
use App\Models\Group;
use App\Models\Informant;
use App\Models\Machine;
use App\Models\Operation;
use App\Models\Part;
use App\Models\PartSerialNumber;
use App\Models\Position;
use App\Models\Reason;
use App\Models\Shift;
use App\Models\Technician;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class DowntimeApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        if (! Schema::hasTable('reportings')) {
            Schema::create('reportings', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('area_id')->nullable();
                $table->unsignedBigInteger('division_id')->nullable();
                $table->unsignedBigInteger('shift_id_reporting')->nullable();
                $table->unsignedBigInteger('shift_id_start')->nullable();
                $table->unsignedBigInteger('shift_id_finish')->nullable();
                $table->unsignedBigInteger('machine_id')->nullable();
                $table->unsignedBigInteger('position_id')->nullable();
                $table->unsignedBigInteger('part_id')->nullable();
                $table->unsignedBigInteger('operation_id')->nullable();
                $table->unsignedBigInteger('operation_id_actual')->nullable();
                $table->unsignedBigInteger('reason_id')->nullable();
                $table->unsignedBigInteger('informant_id')->nullable();
                $table->unsignedBigInteger('approved_by')->nullable();
                $table->unsignedBigInteger('part_serial_number_id')->nullable();
                $table->string('reporting_number')->nullable();
                $table->dateTime('reporting_date')->nullable();
                $table->text('reporting_notes')->nullable();
                $table->dateTime('processing_date_start')->nullable();
                $table->dateTime('processing_date_finish')->nullable();
                $table->unsignedTinyInteger('reporting_type')->nullable();
                $table->unsignedTinyInteger('status')->default(1);
                $table->text('notes')->nullable();
                $table->text('approved_notes')->nullable();
                $table->dateTime('approved_at')->nullable();
            });
        }

        if (! Schema::hasTable('reporting_technician')) {
            Schema::create('reporting_technician', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('reporting_id');
                $table->unsignedBigInteger('technician_id');
            });
        }
    }

    public function test_authenticated_user_can_list_downtimes_with_summary(): void
    {
        $area = Area::factory()->create();
        $group = Group::factory()->forArea($area)->create();
        $division = Division::factory()->forArea($area)->create();
        $shift = Shift::factory()->forArea($area)->create(['name' => 'Shift Pagi']);
        $machine = Machine::factory()->forArea($area)->create();
        $position = Position::factory()->forArea($area)->create();
        $part = Part::factory()->forArea($area)->create();
        $operation = Operation::factory()->forArea($area)->create();
        $reason = Reason::factory()->forArea($area)->create();
        $informant = Informant::factory()->forArea($area)->create(['group_id' => $group->id]);
        $approver = Informant::factory()->forArea($area)->create();
        $partSerialNumber = PartSerialNumber::factory()->forArea($area)->forPart($part)->create();
        $technician = Technician::factory()->forArea($area)->create();
        $user = User::factory()->create(['area_id' => $area->id]);

        DB::table('reportings')->insert([
            'id' => 1,
            'area_id' => $area->id,
            'division_id' => $division->id,
            'shift_id_reporting' => $shift->id,
            'shift_id_start' => $shift->id,
            'shift_id_finish' => $shift->id,
            'machine_id' => $machine->id,
            'position_id' => $position->id,
            'part_id' => $part->id,
            'operation_id' => $operation->id,
            'operation_id_actual' => $operation->id,
            'reason_id' => $reason->id,
            'informant_id' => $informant->id,
            'approved_by' => $approver->id,
            'part_serial_number_id' => $partSerialNumber->id,
            'reporting_number' => 'RPT-001',
            'reporting_date' => '2026-04-01 08:00:00',
            'reporting_notes' => 'Downtime utama',
            'processing_date_start' => '2026-04-01 08:15:00',
            'processing_date_finish' => '2026-04-01 09:15:00',
            'reporting_type' => 1,
            'status' => 5,
            'notes' => 'Catatan maintenance',
            'approved_notes' => 'Disetujui',
            'approved_at' => '2026-04-01 10:00:00',
        ]);

        DB::table('reporting_technician')->insert([
            'reporting_id' => 1,
            'technician_id' => $technician->id,
        ]);

        $token = auth('api')->login($user);

        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/downtimes?per_page=10');

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('meta.total', 1)
            ->assertJsonPath('data.0.reporting_number', 'RPT-001')
            ->assertJsonPath('data.0.machine_id', (string) $machine->id)
            ->assertJsonPath('data.0.technician_names.0', $technician->name)
            ->assertJsonPath('meta.summary.total_time_finishing', '01:00:00');
    }

    public function test_authenticated_user_can_filter_downtimes_by_technician(): void
    {
        $area = Area::factory()->create();
        $group = Group::factory()->forArea($area)->create();
        $division = Division::factory()->forArea($area)->create();
        $shift = Shift::factory()->forArea($area)->create();
        $machine = Machine::factory()->forArea($area)->create();
        $position = Position::factory()->forArea($area)->create();
        $part = Part::factory()->forArea($area)->create();
        $operation = Operation::factory()->forArea($area)->create();
        $reason = Reason::factory()->forArea($area)->create();
        $informant = Informant::factory()->forArea($area)->create(['group_id' => $group->id]);
        $partSerialNumber = PartSerialNumber::factory()->forArea($area)->forPart($part)->create();
        $technicianOne = Technician::factory()->forArea($area)->create();
        $technicianTwo = Technician::factory()->forArea($area)->create();
        $user = User::factory()->create(['area_id' => $area->id]);

        DB::table('reportings')->insert([
            [
                'id' => 1,
                'area_id' => $area->id,
                'division_id' => $division->id,
                'shift_id_reporting' => $shift->id,
                'shift_id_start' => $shift->id,
                'shift_id_finish' => $shift->id,
                'machine_id' => $machine->id,
                'position_id' => $position->id,
                'part_id' => $part->id,
                'operation_id' => $operation->id,
                'operation_id_actual' => $operation->id,
                'reason_id' => $reason->id,
                'informant_id' => $informant->id,
                'part_serial_number_id' => $partSerialNumber->id,
                'reporting_number' => 'RPT-001',
                'reporting_date' => '2026-04-01 08:00:00',
                'processing_date_start' => '2026-04-01 08:15:00',
                'processing_date_finish' => '2026-04-01 09:15:00',
                'status' => 5,
            ],
            [
                'id' => 2,
                'area_id' => $area->id,
                'division_id' => $division->id,
                'shift_id_reporting' => $shift->id,
                'shift_id_start' => $shift->id,
                'shift_id_finish' => $shift->id,
                'machine_id' => $machine->id,
                'position_id' => $position->id,
                'part_id' => $part->id,
                'operation_id' => $operation->id,
                'operation_id_actual' => $operation->id,
                'reason_id' => $reason->id,
                'informant_id' => $informant->id,
                'part_serial_number_id' => $partSerialNumber->id,
                'reporting_number' => 'RPT-002',
                'reporting_date' => '2026-04-02 08:00:00',
                'processing_date_start' => '2026-04-02 08:15:00',
                'processing_date_finish' => '2026-04-02 09:15:00',
                'status' => 5,
            ],
        ]);

        DB::table('reporting_technician')->insert([
            ['reporting_id' => 1, 'technician_id' => $technicianOne->id],
            ['reporting_id' => 2, 'technician_id' => $technicianTwo->id],
        ]);

        $token = auth('api')->login($user);

        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/downtimes?technician_id='.$technicianOne->id);

        $response->assertOk()
            ->assertJsonPath('meta.total', 1)
            ->assertJsonPath('data.0.reporting_number', 'RPT-001');
    }

    public function test_downtime_returns_validation_error_when_filter_is_invalid(): void
    {
        $user = User::factory()->create();
        $token = auth('api')->login($user);

        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/downtimes?period_start=invalid-date');

        $response->assertStatus(400)
            ->assertJsonPath('success', false)
            ->assertJsonPath('message', 'Bad request')
            ->assertJsonStructure([
                'errors' => ['period_start'],
            ]);
    }

    public function test_downtime_requires_authentication(): void
    {
        $response = $this->getJson('/api/downtimes');

        $response->assertStatus(401)
            ->assertJsonPath('success', false)
            ->assertJsonPath('message', 'Unauthorized');
    }
}
