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

class MttrApiTest extends TestCase
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
                $table->unsignedBigInteger('operation_id')->nullable();
                $table->unsignedBigInteger('shift_id_start')->nullable();
                $table->dateTime('reporting_date')->nullable();
                $table->dateTime('processing_date_start')->nullable();
                $table->dateTime('processing_date_finish')->nullable();
                $table->unsignedTinyInteger('reporting_type')->nullable();
                $table->unsignedTinyInteger('status')->default(1);
            });
        }
    }

    public function test_authenticated_user_can_get_monthly_mttr_data(): void
    {
        $area = Area::factory()->create();
        $user = User::factory()->create(['area_id' => $area->id]);
        Fbdt::factory()->create(['area_id' => $area->id, 'tahun' => 2026, 'bulan' => 4, 'mttr' => 30]);

        DB::table('reportings')->insert([
            'area_id' => $area->id,
            'reporting_date' => '2026-04-01 08:00:00',
            'processing_date_start' => '2026-04-01 08:00:00',
            'processing_date_finish' => '2026-04-01 09:30:00',
            'reporting_type' => 1,
            'status' => 5,
        ]);

        $token = auth('api')->login($user);

        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/mttr?type=monthly&year=2026');

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonFragment(['date' => 'Apr-2026']);
    }

    public function test_mttr_returns_validation_error_when_required_year_is_missing(): void
    {
        $user = User::factory()->create();
        $token = auth('api')->login($user);

        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/mttr?type=monthly');

        $response->assertStatus(400)
            ->assertJsonPath('success', false)
            ->assertJsonStructure([
                'errors' => ['year'],
            ]);
    }
}
