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
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class ReportingApiTest extends TestCase
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
                $table->unsignedBigInteger('machine_id')->nullable();
                $table->unsignedBigInteger('position_id')->nullable();
                $table->unsignedBigInteger('part_id')->nullable();
                $table->unsignedBigInteger('part_serial_number_id')->nullable();
                $table->unsignedBigInteger('operation_id')->nullable();
                $table->unsignedBigInteger('reason_id')->nullable();
                $table->unsignedBigInteger('informant_id')->nullable();
                $table->string('reporting_number')->nullable();
                $table->unsignedInteger('sort_order')->nullable();
                $table->dateTime('reporting_date')->nullable();
                $table->text('reporting_notes')->nullable();
                $table->unsignedTinyInteger('reporting_type')->nullable();
                $table->unsignedTinyInteger('status')->default(1);
                $table->timestamps();
            });
        }
    }

    public function test_authenticated_user_can_list_reportings_with_pagination(): void
    {
        $fixtures = $this->fixtures();
        $this->insertReporting($fixtures, ['reporting_number' => 'RPT-001', 'status' => 2]);
        $this->insertReporting($fixtures, ['reporting_number' => 'RPT-002', 'status' => 3]);
        $this->insertReporting($fixtures, ['reporting_number' => 'RPT-NEW', 'status' => 1]);
        $this->insertReporting($this->fixtures(), ['reporting_number' => 'OTHER']);
        $token = auth('api')->login($fixtures['user']);

        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/reportings?per_page=10&search=RPT');

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('message', 'Data retrieved successfully')
            ->assertJsonPath('meta.total', 2)
            ->assertJsonPath('data.0.reporting_number', 'RPT-002')
            ->assertJsonPath('data.0.machine_name', 'Machine A');

        $this->assertNotContains('RPT-NEW', collect($response->json('data'))->pluck('reporting_number')->all());
    }

    public function test_authenticated_user_can_create_reporting(): void
    {
        $fixtures = $this->fixtures();
        $token = auth('api')->login($fixtures['user']);

        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson('/api/reportings', $this->payload($fixtures));

        $response->assertStatus(201)
            ->assertJsonPath('success', true)
            ->assertJsonPath('message', 'Reporting created successfully')
            ->assertJsonPath('data.machine_name', 'Machine A')
            ->assertJsonPath('data.shift_reporting_name', 'Shift Full Day')
            ->assertJsonPath('data.reporting_type', 1)
            ->assertJsonPath('data.status', 1);

        $this->assertDatabaseHas('reportings', [
            'area_id' => $fixtures['area']->id,
            'machine_id' => $fixtures['machine']->id,
            'shift_id_reporting' => $fixtures['shift']->id,
            'status' => 1,
        ]);
    }

    public function test_authenticated_user_can_view_update_and_delete_reporting(): void
    {
        $fixtures = $this->fixtures();
        $reportingId = $this->insertReporting($fixtures, ['reporting_number' => 'RPT-DETAIL']);
        $newOperation = Operation::factory()->forArea($fixtures['area'])->create(['name' => 'New Operation']);
        $token = auth('api')->login($fixtures['user']);

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/reportings/'.$reportingId)
            ->assertOk()
            ->assertJsonPath('data.reporting_number', 'RPT-DETAIL')
            ->assertJsonPath('data.operation_name', 'Operation A');

        $payload = $this->payload($fixtures, [
            'operation_id' => $newOperation->id,
            'reporting_notes' => 'Updated reporting notes',
        ]);

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->putJson('/api/reportings/'.$reportingId, $payload)
            ->assertOk()
            ->assertJsonPath('message', 'Reporting updated successfully')
            ->assertJsonPath('data.operation_name', 'New Operation')
            ->assertJsonPath('data.reporting_notes', 'Updated reporting notes');

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->deleteJson('/api/reportings/'.$reportingId)
            ->assertOk()
            ->assertJsonPath('message', 'Reporting deleted successfully')
            ->assertJsonPath('data.status', 99);

        $this->assertDatabaseHas('reportings', [
            'id' => $reportingId,
            'status' => 99,
        ]);
    }

    public function test_reporting_types_and_time_are_available(): void
    {
        $fixtures = $this->fixtures();
        $token = auth('api')->login($fixtures['user']);

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/reportings/types')
            ->assertOk()
            ->assertJsonPath('data.0.id', 1)
            ->assertJsonPath('data.0.text', 'mechanical');

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/reportings/time?reporting_date=2026-04-22 10:00:00')
            ->assertOk()
            ->assertJsonPath('data.shift_id', (string) $fixtures['shift']->id)
            ->assertJsonPath('data.shift_name', 'Shift Full Day');
    }

    public function test_create_reporting_returns_validation_error_when_payload_is_invalid(): void
    {
        $user = User::factory()->create();
        $token = auth('api')->login($user);

        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson('/api/reportings', []);

        $response->assertStatus(400)
            ->assertJsonPath('success', false)
            ->assertJsonPath('message', 'Bad request')
            ->assertJsonStructure([
                'errors' => ['machine_id', 'position_id', 'part_id', 'division_id', 'operation_id', 'reason_id', 'informant_id', 'reporting_type', 'reporting_date'],
            ]);
    }

    public function test_reporting_requires_authentication(): void
    {
        $this->getJson('/api/reportings')
            ->assertStatus(401)
            ->assertJsonPath('success', false)
            ->assertJsonPath('message', 'Unauthorized');
    }

    private function fixtures(): array
    {
        $area = Area::factory()->create();
        $group = Group::factory()->forArea($area)->create();

        return [
            'area' => $area,
            'user' => User::factory()->create(['area_id' => $area->id]),
            'division' => Division::factory()->forArea($area)->create(['name' => 'Division A']),
            'machine' => Machine::factory()->forArea($area)->create(['name' => 'Machine A']),
            'position' => Position::factory()->forArea($area)->create(['name' => 'Position A']),
            'part' => Part::factory()->forArea($area)->create(['name' => 'Part A']),
            'partSerialNumber' => PartSerialNumber::factory()->forArea($area)->create(['serial_number' => 'SN-A']),
            'operation' => Operation::factory()->forArea($area)->create(['name' => 'Operation A']),
            'reason' => Reason::factory()->forArea($area)->create(['name' => 'Reason A']),
            'informant' => Informant::factory()->forArea($area)->create([
                'group_id' => $group->id,
                'name' => 'Informant A',
            ]),
            'shift' => Shift::factory()->forArea($area)->create([
                'name' => 'Shift Full Day',
                'time_start' => '00:00:00',
                'time_finish' => '23:59:59',
            ]),
        ];
    }

    private function payload(array $fixtures, array $overrides = []): array
    {
        return [
            'machine_id' => $fixtures['machine']->id,
            'position_id' => $fixtures['position']->id,
            'part_id' => $fixtures['part']->id,
            'part_serial_number_id' => $fixtures['partSerialNumber']->id,
            'division_id' => $fixtures['division']->id,
            'operation_id' => $fixtures['operation']->id,
            'reason_id' => $fixtures['reason']->id,
            'informant_id' => $fixtures['informant']->id,
            'reporting_type' => 1,
            'reporting_date' => '2026-04-22 10:00:00',
            'reporting_notes' => 'Initial reporting notes',
            ...$overrides,
        ];
    }

    private function insertReporting(array $fixtures, array $overrides = []): int
    {
        return DB::table('reportings')->insertGetId([
            'area_id' => $fixtures['area']->id,
            'division_id' => $fixtures['division']->id,
            'shift_id_reporting' => $fixtures['shift']->id,
            'machine_id' => $fixtures['machine']->id,
            'position_id' => $fixtures['position']->id,
            'part_id' => $fixtures['part']->id,
            'part_serial_number_id' => $fixtures['partSerialNumber']->id,
            'operation_id' => $fixtures['operation']->id,
            'reason_id' => $fixtures['reason']->id,
            'informant_id' => $fixtures['informant']->id,
            'reporting_number' => 'RPT-001',
            'sort_order' => 1,
            'reporting_date' => '2026-04-22 10:00:00',
            'reporting_notes' => 'Initial reporting notes',
            'reporting_type' => 1,
            'status' => 1,
            'created_at' => now(),
            'updated_at' => now(),
            ...$overrides,
        ]);
    }
}
