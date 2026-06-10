<?php

namespace Tests\Unit\Support;

use App\Support\ApiResponse;
use Illuminate\Support\Str;
use Tests\TestCase;

class ApiResponseTest extends TestCase
{
    public function test_error_response_uses_the_standard_payload(): void
    {
        $response = ApiResponse::error(
            code: 'VALIDATION_ERROR',
            message: 'Dados invalidos',
            details: ['field' => ['Campo obrigatorio']],
            status: 422,
        );

        $payload = $response->getData(true);

        $this->assertSame(422, $response->getStatusCode());
        $this->assertSame([
            'error' => [
                'code' => 'VALIDATION_ERROR',
                'message' => 'Dados invalidos',
                'details' => [
                    'field' => ['Campo obrigatorio'],
                ],
            ],
            'meta' => [
                'request_id' => $payload['meta']['request_id'],
            ],
        ], $payload);
        $this->assertTrue(Str::isUuid($payload['meta']['request_id']));
        $this->assertSame(
            $payload['meta']['request_id'],
            $response->headers->get('X-Request-ID'),
        );
    }

    public function test_success_response_uses_the_standard_payload(): void
    {
        $response = ApiResponse::success(['status' => 'online']);
        $payload = $response->getData(true);

        $this->assertSame([
            'data' => [
                'status' => 'online',
            ],
            'meta' => [
                'request_id' => $payload['meta']['request_id'],
            ],
        ], $payload);
        $this->assertTrue(Str::isUuid($payload['meta']['request_id']));
    }
}
