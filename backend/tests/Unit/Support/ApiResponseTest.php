<?php

namespace Tests\Unit\Support;

use App\Support\ApiResponse;
use Tests\TestCase;

class ApiResponseTest extends TestCase
{
    public function test_error_response_uses_the_standard_payload(): void
    {
        $response = ApiResponse::error(
            message: 'Dados invalidos',
            errors: ['field' => ['Campo obrigatorio']],
            status: 422,
        );

        $this->assertSame(422, $response->getStatusCode());
        $this->assertSame([
            'success' => false,
            'message' => 'Dados invalidos',
            'data' => null,
            'errors' => [
                'field' => ['Campo obrigatorio'],
            ],
        ], $response->getData(true));
    }
}
