<?php

namespace Tests\Feature\Api;

use Illuminate\Support\Str;
use Tests\TestCase;

class HealthTest extends TestCase
{
    public function test_health_endpoint_returns_the_expected_payload(): void
    {
        $response = $this->getJson('/api/v1/health');

        $requestId = $response->json('meta.request_id');

        $response
            ->assertOk()
            ->assertHeader('content-type', 'application/json')
            ->assertHeader('X-Request-ID', $requestId)
            ->assertJson([
                'data' => [
                    'app' => 'Gabarito360',
                    'status' => 'online',
                ],
                'meta' => [
                    'request_id' => $requestId,
                ],
            ]);

        $this->assertTrue(Str::isUuid($requestId));
    }
}
