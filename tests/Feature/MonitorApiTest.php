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
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class MonitorApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_can_get_monitor_data(): void
    {
        $fixtures = $this->fixtures();
        $otherFixtures = $this->fixtures();
        $technician = Technician::factory()->forArea($fixtures['area'])->create(['name' => 'Tech A']);
        $user = User::factory()->create(['area_id' => $fixtures['area']->id]);

        $recentId = $this->insertReporting($fixtures, [
            'reporting_number' => 'MON-RECENT',
            'reporting_date' => '2026-04-21 08:00:00',
            'status' => 5,
            'sort_order' => 1,
        ]);
        $previousOpenId = $this->insertReporting($fixtures, [
            'reporting_number' => 'MON-PREVIOUS',
            'reporting_date' => '2026-04-18 08:00:00',
            'status' => 2,
            'sort_order' => 2,
        ]);
        $this->insertReporting($fixtures, [
            'reporting_number' => 'MON-OLD-EXTEND',
            'reporting_date' => '2026-04-18 09:00:00',
            'status' => 3,
            'sort_order' => 3,
        ]);
        $this->insertReporting($otherFixtures, [
            'reporting_number' => 'MON-OTHER-AREA',
            'reporting_date' => '2026-04-21 08:00:00',
            'status' => 5,
            'sort_order' => 1,
        ]);

        DB::table('reporting_technician')->insert([
            'reporting_id' => $recentId,
            'technician_id' => $technician->id,
        ]);

        $token = auth('api')->login($user);

        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/monitor?period_start=2026-04-20&period_end=2026-04-21&per_page=10');

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('message', 'Data retrieved successfully')
            ->assertJsonPath('meta.current_page', 1)
            ->assertJsonPath('meta.per_page', 10)
            ->assertJsonPath('meta.total', 2)
            ->assertJsonPath('meta.period_start', '2026-04-20')
            ->assertJsonPath('meta.previous_period_start', '2026-04-17')
            ->assertJsonPath('data.0.reporting_number', 'MON-RECENT')
            ->assertJsonPath('data.0.status_name', 'finish')
            ->assertJsonPath('data.0.technician_names.0', 'Tech A')
            ->assertJsonPath('data.1.reporting_number', 'MON-PREVIOUS')
            ->assertJsonPath('data.1.status_name', 'on_progress');

        $reportingNumbers = collect($response->json('data'))->pluck('reporting_number')->all();

        $this->assertContains('MON-RECENT', $reportingNumbers);
        $this->assertContains('MON-PREVIOUS', $reportingNumbers);
        $this->assertNotContains('MON-OLD-EXTEND', $reportingNumbers);
        $this->assertNotContains('MON-OTHER-AREA', $reportingNumbers);
        $this->assertDatabaseHas('reportings', [
            'id' => $previousOpenId,
            'status' => 2,
        ]);
    }

    public function test_monitor_returns_validation_error_for_invalid_period(): void
    {
        $user = User::factory()->create();
        $token = auth('api')->login($user);

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/monitor?period_start=invalid-date')
            ->assertStatus(400)
            ->assertJsonPath('success', false)
            ->assertJsonPath('message', 'Bad request')
            ->assertJsonStructure([
                'errors' => ['period_start'],
            ]);
    }

    public function test_monitor_returns_bad_request_when_period_start_is_after_period_end(): void
    {
        $user = User::factory()->create();
        $token = auth('api')->login($user);

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/monitor?period_start=2026-04-22&period_end=2026-04-21')
            ->assertStatus(400)
            ->assertJsonPath('success', false)
            ->assertJsonPath('message', 'Bad request')
            ->assertJsonStructure([
                'errors' => ['period_start'],
            ]);
    }

    public function test_monitor_requires_authentication(): void
    {
        $this->getJson('/api/monitor')
            ->assertStatus(401)
            ->assertJsonPath('success', false)
            ->assertJsonPath('message', 'Unauthorized');
    }

    private function fixtures(): array
    {
        $area = Area::factory()->create();
        $group = Group::factory()->forArea($area)->create();
        $part = Part::factory()->forArea($area)->create(['name' => 'Part A']);

        return [
            'area' => $area,
            'division' => Division::factory()->forArea($area)->create(['name' => 'Division A']),
            'machine' => Machine::factory()->forArea($area)->create(['name' => 'Machine A']),
            'position' => Position::factory()->forArea($area)->create(['name' => 'Position A']),
            'part' => $part,
            'partSerialNumber' => PartSerialNumber::factory()->forArea($area)->forPart($part)->create(['serial_number' => 'SN-A']),
            'operation' => Operation::factory()->forArea($area)->create(['name' => 'Operation A']),
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
            'reporting_number' => 'MON-001',
            'machine_id' => $fixtures['machine']->id,
            'position_id' => $fixtures['position']->id,
            'part_id' => $fixtures['part']->id,
            'division_id' => $fixtures['division']->id,
            'operation_id' => $fixtures['operation']->id,
            'reason_id' => $fixtures['reason']->id,
            'reporting_notes' => 'Monitor notes',
            'informant_id' => $fixtures['informant']->id,
            'shift_id_reporting' => $fixtures['shift']->id,
            'reporting_date' => '2026-04-21 08:00:00',
            'processing_date_start' => '2026-04-21 09:00:00',
            'processing_date_finish' => '2026-04-21 10:00:00',
            'total_time_finishing' => '01:00:00',
            'sort_order' => 1,
            'notes' => 'Work notes',
            'status' => 5,
            'reporting_type' => 1,
            'part_serial_number_id' => $fixtures['partSerialNumber']->id,
            'created_at' => now(),
            'updated_at' => now(),
            ...$overrides,
        ]);
    }
}
