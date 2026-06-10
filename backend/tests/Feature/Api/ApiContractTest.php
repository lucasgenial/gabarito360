<?php

namespace Tests\Feature\Api;

use App\Support\ApiResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use RuntimeException;
use Tests\TestCase;

class ApiContractTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Route::get('/api/v1/_contract/log-context', function () {
            return ApiResponse::success([
                'log_request_id' => Log::sharedContext()['request_id'] ?? null,
            ]);
        });

        Route::get('/api/v1/_contract/validation-error', function () {
            throw ValidationException::withMessages([
                'campo' => ['O campo e obrigatorio.'],
            ]);
        });

        Route::get('/api/v1/_contract/internal-error', function () {
            throw new RuntimeException('detalhe interno sensivel');
        });
    }

    public function test_success_response_contains_consistent_request_id(): void
    {
        $requestId = (string) Str::uuid();

        $response = $this
            ->withHeader('X-Request-ID', $requestId)
            ->getJson('/api/v1/health');

        $response
            ->assertOk()
            ->assertHeader('X-Request-ID', $requestId)
            ->assertJsonPath('meta.request_id', $requestId)
            ->assertJsonMissingPath('success')
            ->assertJsonMissingPath('message');
    }

    public function test_invalid_request_id_is_replaced_and_shared_with_logs(): void
    {
        $response = $this
            ->withHeader('X-Request-ID', 'identificador-invalido')
            ->getJson('/api/v1/_contract/log-context');

        $requestId = $response->json('meta.request_id');

        $response
            ->assertOk()
            ->assertHeader('X-Request-ID', $requestId)
            ->assertJsonPath('data.log_request_id', $requestId);

        $this->assertTrue(Str::isUuid($requestId));
    }

    public function test_validation_error_uses_stable_error_contract(): void
    {
        $response = $this->getJson('/api/v1/_contract/validation-error');
        $requestId = $response->json('meta.request_id');

        $response
            ->assertUnprocessable()
            ->assertHeader('X-Request-ID', $requestId)
            ->assertJsonPath('error.code', 'VALIDATION_ERROR')
            ->assertJsonPath('error.message', 'Os dados informados sao invalidos.')
            ->assertJsonPath('error.details.campo.0', 'O campo e obrigatorio.')
            ->assertJsonPath('meta.request_id', $requestId);
    }

    public function test_missing_api_route_uses_stable_error_contract(): void
    {
        $response = $this->getJson('/api/v1/recurso-inexistente');
        $requestId = $response->json('meta.request_id');

        $response
            ->assertNotFound()
            ->assertHeader('X-Request-ID', $requestId)
            ->assertJsonPath('error.code', 'RESOURCE_NOT_FOUND')
            ->assertJsonPath('error.message', 'Recurso nao encontrado.')
            ->assertJsonPath('meta.request_id', $requestId);
    }

    public function test_internal_exception_does_not_expose_sensitive_details(): void
    {
        $response = $this->getJson('/api/v1/_contract/internal-error');
        $requestId = $response->json('meta.request_id');

        $response
            ->assertInternalServerError()
            ->assertHeader('X-Request-ID', $requestId)
            ->assertJsonPath('error.code', 'INTERNAL_ERROR')
            ->assertJsonPath('error.message', 'Erro interno do servidor.')
            ->assertJsonPath('meta.request_id', $requestId)
            ->assertDontSee('detalhe interno sensivel');
    }
}
