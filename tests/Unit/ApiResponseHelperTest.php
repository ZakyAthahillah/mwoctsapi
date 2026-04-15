<?php

namespace Tests\Unit;

use App\Helpers\ApiResponseHelper;
use Tests\TestCase;

class ApiResponseHelperTest extends TestCase
{
    public function test_success_returns_standardized_payload(): void
    {
        $response = ApiResponseHelper::success('Data retrieved successfully', [
            'id' => '1',
            'name' => 'Test User',
        ]);

        $payload = $response->getData(true);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertTrue($payload['success']);
        $this->assertSame('Data retrieved successfully', $payload['message']);
        $this->assertSame([
            'id' => '1',
            'name' => 'Test User',
        ], $payload['data']);
        $this->assertNull($payload['meta']);
        $this->assertNull($payload['errors']);
    }

    public function test_error_returns_standardized_payload(): void
    {
        $response = ApiResponseHelper::error('Bad request', [
            'request' => ['At least one field must be provided for update.'],
        ], 400);

        $payload = $response->getData(true);

        $this->assertSame(400, $response->getStatusCode());
        $this->assertFalse($payload['success']);
        $this->assertSame('Bad request', $payload['message']);
        $this->assertNull($payload['data']);
        $this->assertNull($payload['meta']);
        $this->assertSame([
            'request' => ['At least one field must be provided for update.'],
        ], $payload['errors']);
    }
}
