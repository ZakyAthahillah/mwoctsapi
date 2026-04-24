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

class JobApiTest extends TestCase
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
                $table->unsignedBigInteger('shift_id_approved')->nullable();
                $table->unsignedBigInteger('machine_id')->nullable();
                $table->unsignedBigInteger('position_id')->nullable();
                $table->unsignedBigInteger('part_id')->nullable();
                $table->unsignedBigInteger('part_serial_number_id')->nullable();
                $table->unsignedBigInteger('part_serial_number_id_new')->nullable();
                $table->unsignedBigInteger('operation_id')->nullable();
                $table->unsignedBigInteger('operation_id_actual')->nullable();
                $table->unsignedBigInteger('reason_id')->nullable();
                $table->unsignedBigInteger('informant_id')->nullable();
                $table->unsignedBigInteger('approved_by')->nullable();
                $table->string('reporting_number')->nullable();
                $table->dateTime('reporting_date')->nullable();
                $table->text('reporting_notes')->nullable();
                $table->dateTime('processing_date_start')->nullable();
                $table->dateTime('processing_date_finish')->nullable();
                $table->dateTime('approved_at')->nullable();
                $table->unsignedTinyInteger('reporting_type')->nullable();
                $table->unsignedTinyInteger('status')->default(1);
                $table->text('notes')->nullable();
                $table->text('approved_notes')->nullable();
                $table->string('gap_time_response')->nullable();
                $table->string('total_time_finishing')->nullable();
                $table->string('total_time_approved')->nullable();
                $table->timestamps();
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

    public function test_authenticated_user_can_list_jobs_by_status(): void
    {
        $area = Area::factory()->create();
        $group = Group::factory()->forArea($area)->create();
        $division = Division::factory()->forArea($area)->create();
        $informant = Informant::factory()->forArea($area)->create(['group_id' => $group->id]);
        $machine = Machine::factory()->forArea($area)->create();
        $position = Position::factory()->forArea($area)->create();
        $part = Part::factory()->forArea($area)->create();
        $partSerialNumber = PartSerialNumber::factory()->forArea($area)->forPart($part)->create();
        $operation = Operation::factory()->forArea($area)->create();
        $reason = Reason::factory()->forArea($area)->create();
        $shift = Shift::factory()->forArea($area)->create(['name' => 'Shift Pagi']);
        $technician = Technician::factory()->forArea($area)->create();
        $user = User::factory()->create(['area_id' => $area->id]);

        DB::table('reportings')->insert([
            [
                'id' => 1,
                'area_id' => $area->id,
                'division_id' => $division->id,
                'shift_id_reporting' => $shift->id,
                'machine_id' => $machine->id,
                'position_id' => $position->id,
                'part_id' => $part->id,
                'part_serial_number_id' => $partSerialNumber->id,
                'operation_id' => $operation->id,
                'reason_id' => $reason->id,
                'informant_id' => $informant->id,
                'reporting_number' => 'JOB-001',
                'reporting_date' => '2026-04-01 08:00:00',
                'reporting_notes' => 'New job',
                'reporting_type' => 1,
                'sort_order' => 1,
                'status' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => 2,
                'area_id' => $area->id,
                'division_id' => $division->id,
                'shift_id_reporting' => $shift->id,
                'machine_id' => $machine->id,
                'position_id' => $position->id,
                'part_id' => $part->id,
                'part_serial_number_id' => $partSerialNumber->id,
                'operation_id' => $operation->id,
                'reason_id' => $reason->id,
                'informant_id' => $informant->id,
                'reporting_number' => 'JOB-002',
                'reporting_date' => '2026-04-02 08:00:00',
                'reporting_notes' => 'Finished job',
                'reporting_type' => 1,
                'sort_order' => 2,
                'status' => 5,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => 3,
                'area_id' => $area->id,
                'division_id' => $division->id,
                'shift_id_reporting' => $shift->id,
                'machine_id' => $machine->id,
                'position_id' => $position->id,
                'part_id' => $part->id,
                'part_serial_number_id' => $partSerialNumber->id,
                'operation_id' => $operation->id,
                'reason_id' => $reason->id,
                'informant_id' => $informant->id,
                'reporting_number' => 'JOB-003',
                'reporting_date' => '2026-04-03 08:00:00',
                'reporting_notes' => 'On progress job',
                'reporting_type' => 1,
                'sort_order' => 3,
                'status' => 2,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => 4,
                'area_id' => $area->id,
                'division_id' => $division->id,
                'shift_id_reporting' => $shift->id,
                'machine_id' => $machine->id,
                'position_id' => $position->id,
                'part_id' => $part->id,
                'part_serial_number_id' => $partSerialNumber->id,
                'operation_id' => $operation->id,
                'reason_id' => $reason->id,
                'informant_id' => $informant->id,
                'reporting_number' => 'JOB-004',
                'reporting_date' => '2026-04-04 08:00:00',
                'reporting_notes' => 'Extended job',
                'reporting_type' => 1,
                'sort_order' => 4,
                'status' => 3,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => 5,
                'area_id' => $area->id,
                'division_id' => $division->id,
                'shift_id_reporting' => $shift->id,
                'machine_id' => $machine->id,
                'position_id' => $position->id,
                'part_id' => $part->id,
                'part_serial_number_id' => $partSerialNumber->id,
                'operation_id' => $operation->id,
                'reason_id' => $reason->id,
                'informant_id' => $informant->id,
                'reporting_number' => 'JOB-005',
                'reporting_date' => '2026-04-05 08:00:00',
                'reporting_notes' => 'Waiting approval job',
                'reporting_type' => 1,
                'sort_order' => 5,
                'status' => 4,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        DB::table('reporting_technician')->insert([
            'reporting_id' => 1,
            'technician_id' => $technician->id,
        ]);

        $token = auth('api')->login($user);

        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/jobs?status=new&per_page=10');

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('meta.total', 1)
            ->assertJsonPath('data.0.reporting_number', 'JOB-001')
            ->assertJsonPath('data.0.status_name', 'new')
            ->assertJsonPath('data.0.technician_names.0', $technician->name);

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/jobs/new?per_page=10')
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('meta.total', 1)
            ->assertJsonPath('data.0.reporting_number', 'JOB-001')
            ->assertJsonPath('data.0.status_name', 'new');

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/jobs/on-progress?per_page=10')
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('meta.total', 1)
            ->assertJsonPath('data.0.reporting_number', 'JOB-003')
            ->assertJsonPath('data.0.status_name', 'on_progress');

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/jobs/extend?per_page=10')
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('meta.total', 1)
            ->assertJsonPath('data.0.reporting_number', 'JOB-004')
            ->assertJsonPath('data.0.status_name', 'extend');

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/jobs/waiting-for-approval?per_page=10')
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('meta.total', 1)
            ->assertJsonPath('data.0.reporting_number', 'JOB-005')
            ->assertJsonPath('data.0.status_name', 'waiting_for_approval');

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/jobs/finish?per_page=10')
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('meta.total', 1)
            ->assertJsonPath('data.0.reporting_number', 'JOB-002')
            ->assertJsonPath('data.0.status_name', 'finish');
    }

    public function test_authenticated_user_can_start_job(): void
    {
        $area = Area::factory()->create();
        $group = Group::factory()->forArea($area)->create();
        $informant = Informant::factory()->forArea($area)->create(['group_id' => $group->id]);
        $division = Division::factory()->forArea($area)->create();
        $machine = Machine::factory()->forArea($area)->create();
        $position = Position::factory()->forArea($area)->create();
        $part = Part::factory()->forArea($area)->create();
        $operation = Operation::factory()->forArea($area)->create();
        $reason = Reason::factory()->forArea($area)->create();
        $shift = Shift::factory()->forArea($area)->create([
            'name' => 'Shift Pagi',
            'time_start' => '08:00',
            'time_finish' => '16:00',
        ]);
        $technician = Technician::factory()->forArea($area)->create();
        $user = User::factory()->create(['area_id' => $area->id]);

        DB::table('reportings')->insert([
            'id' => 1,
            'area_id' => $area->id,
            'division_id' => $division->id,
            'shift_id_reporting' => $shift->id,
            'machine_id' => $machine->id,
            'position_id' => $position->id,
            'part_id' => $part->id,
            'operation_id' => $operation->id,
            'reason_id' => $reason->id,
            'informant_id' => $informant->id,
            'reporting_number' => 'JOB-START',
            'reporting_date' => '2026-04-01 08:00:00',
            'reporting_type' => 1,
            'sort_order' => 1,
            'status' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $token = auth('api')->login($user);

        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->putJson('/api/jobs/1/start', [
                'technician_id' => $technician->id,
                'processing_date_start' => '2026-04-01 09:00:00',
            ]);

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.status', 2)
            ->assertJsonPath('data.shift_id_start', (string) $shift->id);

        $this->assertDatabaseHas('processings', [
            'reporting_id' => 1,
            'status' => 2,
        ]);
    }

    public function test_authenticated_user_can_start_extended_job(): void
    {
        $area = Area::factory()->create();
        $group = Group::factory()->forArea($area)->create();
        $informant = Informant::factory()->forArea($area)->create(['group_id' => $group->id]);
        $division = Division::factory()->forArea($area)->create();
        $machine = Machine::factory()->forArea($area)->create();
        $position = Position::factory()->forArea($area)->create();
        $part = Part::factory()->forArea($area)->create();
        $operation = Operation::factory()->forArea($area)->create();
        $reason = Reason::factory()->forArea($area)->create();
        $shift = Shift::factory()->forArea($area)->create([
            'name' => 'Shift Pagi',
            'time_start' => '08:00',
            'time_finish' => '16:00',
        ]);
        $technician = Technician::factory()->forArea($area)->create();
        $user = User::factory()->create(['area_id' => $area->id]);

        DB::table('reportings')->insert([
            'id' => 1,
            'area_id' => $area->id,
            'division_id' => $division->id,
            'shift_id_reporting' => $shift->id,
            'machine_id' => $machine->id,
            'position_id' => $position->id,
            'part_id' => $part->id,
            'operation_id' => $operation->id,
            'reason_id' => $reason->id,
            'informant_id' => $informant->id,
            'reporting_number' => 'JOB-EXTEND',
            'reporting_date' => '2026-04-01 08:00:00',
            'processing_date_start' => '2026-04-01 09:00:00',
            'processing_date_finish' => '2026-04-01 10:00:00',
            'reporting_type' => 1,
            'sort_order' => 1,
            'status' => 3,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $token = auth('api')->login($user);

        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->putJson('/api/jobs/1/start-extend', [
                'technician_id' => $technician->id,
                'processing_date_start' => '2026-04-01 11:00:00',
            ]);

        $response->assertOk()
            ->assertJsonPath('data.status', 2);
    }

    public function test_finish_job_returns_validation_error_when_required_field_is_missing(): void
    {
        $user = User::factory()->create();
        $token = auth('api')->login($user);

        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->putJson('/api/jobs/1/finish', [
                'processing_date_finish' => '2026-04-01 10:00:00',
            ]);

        $response->assertStatus(400)
            ->assertJsonPath('success', false)
            ->assertJsonStructure([
                'errors' => ['operation_id_actual'],
            ]);
    }

    public function test_authenticated_user_can_extend_and_approve_job(): void
    {
        $area = Area::factory()->create();
        $group = Group::factory()->forArea($area)->create();
        $informant = Informant::factory()->forArea($area)->create(['group_id' => $group->id]);
        $approver = Informant::factory()->forArea($area)->create();
        $division = Division::factory()->forArea($area)->create();
        $machine = Machine::factory()->forArea($area)->create();
        $position = Position::factory()->forArea($area)->create();
        $part = Part::factory()->forArea($area)->create();
        $operation = Operation::factory()->forArea($area)->create();
        $reason = Reason::factory()->forArea($area)->create();
        $shift = Shift::factory()->forArea($area)->create([
            'name' => 'Shift Pagi',
            'time_start' => '08:00',
            'time_finish' => '16:00',
        ]);
        $user = User::factory()->create(['area_id' => $area->id]);

        DB::table('reportings')->insert([
            'id' => 1,
            'area_id' => $area->id,
            'division_id' => $division->id,
            'shift_id_reporting' => $shift->id,
            'machine_id' => $machine->id,
            'position_id' => $position->id,
            'part_id' => $part->id,
            'operation_id' => $operation->id,
            'reason_id' => $reason->id,
            'informant_id' => $informant->id,
            'reporting_number' => 'JOB-APPROVE',
            'reporting_date' => '2026-04-01 08:00:00',
            'processing_date_start' => '2026-04-01 09:00:00',
            'processing_date_finish' => '2026-04-01 10:00:00',
            'shift_id_start' => $shift->id,
            'reporting_type' => 1,
            'sort_order' => 1,
            'status' => 2,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $token = auth('api')->login($user);

        $extendResponse = $this->withHeader('Authorization', 'Bearer '.$token)
            ->putJson('/api/jobs/1/extend', [
                'processing_date_finish' => '2026-04-01 11:00:00',
                'notes' => 'Need more work',
            ]);

        $extendResponse->assertOk()
            ->assertJsonPath('data.status', 3);

        DB::table('reportings')->where('id', 1)->update([
            'status' => 4,
            'processing_date_finish' => '2026-04-01 11:00:00',
        ]);

        $approveResponse = $this->withHeader('Authorization', 'Bearer '.$token)
            ->putJson('/api/jobs/1/approve', [
                'approved_at' => '2026-04-01 12:00:00',
                'approved_by' => $approver->id,
                'approved_notes' => 'Approved',
            ]);

        $approveResponse->assertOk()
            ->assertJsonPath('data.status', 5)
            ->assertJsonPath('data.approved_by', (string) $approver->id);
    }
}
