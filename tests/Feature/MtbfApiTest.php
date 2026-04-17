<?php

namespace Tests\Feature;

use App\Models\Area;
use App\Models\Fbdt;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class MtbfApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        if (! Schema::hasTable('reportings')) {
            Schema::create('reportings', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('area_id')->nullable();
                $table->unsignedBigInteger('machine_id')->nullable();
                $table->unsignedBigInteger('position_id')->nullable();
                $table->unsignedBigInteger('part_id')->nullable();
                $table->unsignedBigInteger('part_serial_number_id')->nullable();
                $table->unsignedBigInteger('shift_id_start')->nullable();
                $table->dateTime('reporting_date')->nullable();
                $table->dateTime('processing_date_start')->nullable();
                $table->dateTime('processing_date_finish')->nullable();
                $table->unsignedTinyInteger('reporting_type')->nullable();
                $table->unsignedTinyInteger('status')->default(1);
            });
        }
    }

    public function test_authenticated_user_can_get_yearly_mtbf_data(): void
    {
        $area = Area::factory()->create();
        $user = User::factory()->create(['area_id' => $area->id]);
        Fbdt::factory()->create(['area_id' => $area->id, 'tahun' => 2026, 'bulan' => 1, 'mtbf' => 12]);

        DB::table('reportings')->insert([
            [
                'area_id' => $area->id,
                'reporting_date' => '2026-01-10 08:00:00',
                'processing_date_start' => '2026-01-10 08:00:00',
                'processing_date_finish' => '2026-01-10 10:00:00',
                'reporting_type' => 1,
                'status' => 5,
            ],
            [
                'area_id' => $area->id,
                'reporting_date' => '2026-02-10 08:00:00',
                'processing_date_start' => '2026-02-10 08:00:00',
                'processing_date_finish' => '2026-02-10 09:00:00',
                'reporting_type' => 1,
                'status' => 5,
            ],
        ]);

        $token = auth('api')->login($user);

        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/mtbf?type=yearly');

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonFragment(['date' => '2026']);
    }

    public function test_authenticated_user_can_get_daily_mtbf_taskplus_data(): void
    {
        $area = Area::factory()->create();
        $user = User::factory()->create(['area_id' => $area->id]);
        Fbdt::factory()->create(['area_id' => $area->id, 'tahun' => 2026, 'bulan' => 4, 'dt' => 10, 'mtbf' => 20]);

        DB::table('tat_tpds')->insert([
            'area_id' => $area->id,
            'tanggal' => '2026-04-01',
            'tat' => 24,
            'tpd' => 8,
        ]);

        DB::table('reportings')->insert([
            'area_id' => $area->id,
            'reporting_date' => '2026-04-01 08:00:00',
            'processing_date_start' => '2026-04-01 08:00:00',
            'processing_date_finish' => '2026-04-01 10:00:00',
            'reporting_type' => 1,
            'status' => 5,
        ]);

        $token = auth('api')->login($user);

        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/mtbf?type=daily&year=2026&month=4&is_taskplus=1');

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonStructure([
                'data' => [
                    ['date', 'mtbf', 'target', 'downtime', 'total', 'pembanding', 'targetMtc'],
                ],
            ]);
    }

    public function test_mtbf_returns_validation_error_when_type_is_invalid(): void
    {
        $user = User::factory()->create();
        $token = auth('api')->login($user);

        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/mtbf?type=invalid');

        $response->assertStatus(400)
            ->assertJsonPath('success', false)
            ->assertJsonStructure([
                'errors' => ['type'],
            ]);
    }
}
