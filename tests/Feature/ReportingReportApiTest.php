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

class ReportingReportApiTest extends TestCase
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
                $table->unsignedBigInteger('operation_id')->nullable();
                $table->unsignedBigInteger('operation_id_actual')->nullable();
                $table->unsignedBigInteger('reason_id')->nullable();
                $table->unsignedBigInteger('informant_id')->nullable();
                $table->unsignedBigInteger('part_serial_number_id')->nullable();
                $table->string('reporting_number')->nullable();
                $table->dateTime('reporting_date')->nullable();
                $table->text('reporting_notes')->nullable();
                $table->dateTime('processing_date_start')->nullable();
                $table->dateTime('processing_date_finish')->nullable();
                $table->string('total_time_finishing')->nullable();
                $table->unsignedTinyInteger('reporting_type')->nullable();
                $table->unsignedTinyInteger('status')->default(1);
                $table->text('notes')->nullable();
                $table->dateTime('approved_at')->nullable();
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

    public function test_authenticated_user_can_list_reporting_reports_with_pagination(): void
    {
        $fixtures = $this->fixtures();
        $technician = Technician::factory()->forArea($fixtures['area'])->create(['name' => 'Technician A']);
        $this->insertReporting($fixtures, [
            'id' => 1,
            'reporting_number' => 'RPT-001',
            'reporting_date' => '2026-04-10 08:00:00',
        ]);
        $this->insertReporting($fixtures, [
            'id' => 2,
            'reporting_number' => 'RPT-002',
            'reporting_date' => '2026-04-11 08:00:00',
            'total_time_finishing' => '01:30:00',
        ]);
        $this->insertReporting($this->fixtures(), [
            'id' => 3,
            'reporting_number' => 'OTHER-AREA',
            'reporting_date' => '2026-04-12 08:00:00',
        ]);
        $this->insertTechnician(2, $technician->id);
        $token = auth('api')->login($fixtures['user']);

        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/reporting-reports?per_page=10&period_start=2026-04-01&period_end=2026-04-30&search=RPT');

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('message', 'Data retrieved successfully')
            ->assertJsonPath('meta.total', 2)
            ->assertJsonPath('data.0.reporting_number', 'RPT-002')
            ->assertJsonPath('data.0.machine_name', 'Machine A')
            ->assertJsonPath('data.0.technician_names.0', 'Technician A')
            ->assertJsonPath('data.0.total_time_finishing_minutes', 90);
    }

    public function test_authenticated_user_can_filter_reporting_reports_by_status_technician_and_group(): void
    {
        $fixtures = $this->fixtures();
        $technicianOne = Technician::factory()->forArea($fixtures['area'])->create();
        $technicianTwo = Technician::factory()->forArea($fixtures['area'])->create();
        $this->insertReporting($fixtures, [
            'id' => 1,
            'reporting_number' => 'RPT-FINISH',
            'status' => 5,
        ]);
        $this->insertReporting($fixtures, [
            'id' => 2,
            'reporting_number' => 'RPT-NEW',
            'status' => 1,
        ]);
        $this->insertTechnician(1, $technicianOne->id);
        $this->insertTechnician(2, $technicianTwo->id);
        $token = auth('api')->login($fixtures['user']);

        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/reporting-reports?period_start=2026-04-01&period_end=2026-04-30&status=5&technician_id='.$technicianOne->id.'&group_id='.$fixtures['group']->id);

        $response->assertOk()
            ->assertJsonPath('meta.total', 1)
            ->assertJsonPath('data.0.reporting_number', 'RPT-FINISH')
            ->assertJsonPath('data.0.status_name', 'finish');
    }

    public function test_reporting_report_statuses_are_available(): void
    {
        $user = User::factory()->create();
        $token = auth('api')->login($user);

        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/reporting-reports/statuses');

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.0.id', 1)
            ->assertJsonPath('data.0.text', 'new')
            ->assertJsonPath('data.4.id', 5)
            ->assertJsonPath('data.4.text', 'finish');
    }

    public function test_reporting_report_returns_validation_error_when_filter_is_invalid(): void
    {
        $user = User::factory()->create();
        $token = auth('api')->login($user);

        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/reporting-reports?period_start=invalid-date');

        $response->assertStatus(400)
            ->assertJsonPath('success', false)
            ->assertJsonPath('message', 'Bad request')
            ->assertJsonStructure([
                'errors' => ['period_start'],
            ]);
    }

    public function test_reporting_report_requires_authentication(): void
    {
        $this->getJson('/api/reporting-reports')
            ->assertStatus(401)
            ->assertJsonPath('success', false)
            ->assertJsonPath('message', 'Unauthorized');
    }

    private function fixtures(): array
    {
        $area = Area::factory()->create();
        $group = Group::factory()->forArea($area)->create(['name' => 'Group A']);

        return [
            'area' => $area,
            'group' => $group,
            'user' => User::factory()->create(['area_id' => $area->id]),
            'division' => Division::factory()->forArea($area)->create(['name' => 'Division A']),
            'machine' => Machine::factory()->forArea($area)->create(['name' => 'Machine A']),
            'position' => Position::factory()->forArea($area)->create(['name' => 'Position A']),
            'part' => Part::factory()->forArea($area)->create(['name' => 'Part A']),
            'partSerialNumber' => PartSerialNumber::factory()->forArea($area)->create(['serial_number' => 'SN-A']),
            'operation' => Operation::factory()->forArea($area)->create(['name' => 'Operation A']),
            'operationActual' => Operation::factory()->forArea($area)->create(['name' => 'Operation Actual A']),
            'reason' => Reason::factory()->forArea($area)->create(['name' => 'Reason A']),
            'informant' => Informant::factory()->forArea($area)->create([
                'group_id' => $group->id,
                'name' => 'Informant A',
            ]),
            'shift' => Shift::factory()->forArea($area)->create(['name' => 'Shift A']),
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
            'operation_id_actual' => $fixtures['operationActual']->id,
            'reason_id' => $fixtures['reason']->id,
            'informant_id' => $fixtures['informant']->id,
            'reporting_number' => 'RPT-001',
            'sort_order' => 1,
            'reporting_date' => '2026-04-10 08:00:00',
            'reporting_notes' => 'Initial report',
            'processing_date_start' => '2026-04-10 08:15:00',
            'processing_date_finish' => '2026-04-10 09:15:00',
            'total_time_finishing' => '01:00:00',
            'reporting_type' => 1,
            'status' => 1,
            'notes' => 'Maintenance notes',
            'approved_at' => '2026-04-10 10:00:00',
            'created_at' => now(),
            'updated_at' => now(),
            ...$overrides,
        ]);
    }

    private function insertTechnician(int $reportingId, int $technicianId): void
    {
        DB::table('reporting_technician')->insert([
            'reporting_id' => $reportingId,
            'technician_id' => $technicianId,
        ]);
    }
}
