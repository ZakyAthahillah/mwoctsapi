<?php

namespace Tests\Feature;

use App\Models\Area;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class DashboardApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        if (! Schema::hasTable('reportings')) {
            Schema::create('reportings', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('area_id')->nullable();
                $table->unsignedTinyInteger('status')->default(1);
                $table->dateTime('reporting_date')->nullable();
            });
        }
    }

    public function test_authenticated_user_can_get_dashboard_summary(): void
    {
        $area = Area::factory()->create();
        $otherArea = Area::factory()->create();
        $user = User::factory()->create([
            'area_id' => $area->id,
        ]);

        DB::table('reportings')->insert([
            ['area_id' => $area->id, 'status' => 1, 'reporting_date' => now()],
            ['area_id' => $area->id, 'status' => 1, 'reporting_date' => now()],
            ['area_id' => $area->id, 'status' => 2, 'reporting_date' => now()],
            ['area_id' => $area->id, 'status' => 5, 'reporting_date' => now()],
            ['area_id' => $otherArea->id, 'status' => 5, 'reporting_date' => now()],
        ]);

        $token = auth('api')->login($user);

        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/dashboard');

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.reporting.new', 2)
            ->assertJsonPath('data.reporting.on_progress', 1)
            ->assertJsonPath('data.reporting.extend', 0)
            ->assertJsonPath('data.reporting.approval', 0)
            ->assertJsonPath('data.reporting.finish', 1);
    }

    public function test_dashboard_requires_authentication(): void
    {
        $response = $this->getJson('/api/dashboard');

        $response->assertStatus(401)
            ->assertJsonPath('success', false)
            ->assertJsonPath('message', 'Unauthorized');
    }
}
