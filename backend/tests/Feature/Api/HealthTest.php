<?php

namespace Tests\Feature\Api;

use Tests\TestCase;

class HealthTest extends TestCase
{
    public function test_health_endpoint_returns_the_expected_payload(): void
    {
        $response = $this->getJson('/api/health');

        $response
            ->assertOk()
            ->assertHeader('content-type', 'application/json')
            ->assertExactJson([
                'success' => true,
                'message' => 'API Gabarito360 online',
                'data' => [
                    'app' => 'Gabarito360',
                    'status' => 'online',
                ],
            ]);
    }
}
