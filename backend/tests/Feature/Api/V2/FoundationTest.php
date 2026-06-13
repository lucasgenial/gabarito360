<?php

namespace Tests\Feature\Api\V2;

use App\Support\ApiResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use RuntimeException;
use Tests\TestCase;

class FoundationTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Route::get('/api/v2/_contract/log-context', function () {
            return ApiResponse::success([
                'log_request_id' => Log::sharedContext()['request_id'] ?? null,
            ]);
        });

        Route::get('/api/v2/_contract/validation-error', function () {
            throw ValidationException::withMessages([
                'campo' => ['O campo e obrigatorio.'],
            ]);
        });

        Route::get('/api/v2/_contract/internal-error', function () {
            throw new RuntimeException('detalhe interno sensivel');
        });
    }

    public function test_health_endpoint_returns_expected_payload(): void
    {
        $response = $this->getJson('/api/v2/health');

        $requestId = $response->json('meta.request_id');

        $response
            ->assertOk()
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

    public function test_success_response_contains_consistent_request_id(): void
    {
        $requestId = (string) Str::uuid();

        $response = $this
            ->withHeader('X-Request-ID', $requestId)
            ->getJson('/api/v2/health');

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
            ->getJson('/api/v2/_contract/log-context');

        $requestId = $response->json('meta.request_id');

        $response
            ->assertOk()
            ->assertHeader('X-Request-ID', $requestId)
            ->assertJsonPath('data.log_request_id', $requestId);

        $this->assertTrue(Str::isUuid($requestId));
    }

    public function test_validation_error_uses_the_v2_error_envelope(): void
    {
        $response = $this->getJson('/api/v2/_contract/validation-error');
        $requestId = $response->json('meta.request_id');

        $response
            ->assertUnprocessable()
            ->assertHeader('X-Request-ID', $requestId)
            ->assertJsonPath('meta.code', 'VALIDATION_ERROR')
            ->assertJsonPath('message', 'Os dados informados sao invalidos.')
            ->assertJsonPath('errors.campo.0', 'O campo e obrigatorio.')
            ->assertJsonPath('meta.request_id', $requestId);
    }

    public function test_missing_api_route_uses_the_v2_error_envelope(): void
    {
        $response = $this->getJson('/api/v2/recurso-inexistente');
        $requestId = $response->json('meta.request_id');

        $response
            ->assertNotFound()
            ->assertHeader('X-Request-ID', $requestId)
            ->assertJsonPath('meta.code', 'RESOURCE_NOT_FOUND')
            ->assertJsonPath('message', 'Recurso nao encontrado.')
            ->assertJsonPath('meta.request_id', $requestId);
    }

    public function test_internal_exception_does_not_expose_sensitive_details(): void
    {
        $response = $this->getJson('/api/v2/_contract/internal-error');
        $requestId = $response->json('meta.request_id');

        $response
            ->assertInternalServerError()
            ->assertHeader('X-Request-ID', $requestId)
            ->assertJsonPath('meta.code', 'INTERNAL_ERROR')
            ->assertJsonPath('message', 'Erro interno do servidor.')
            ->assertJsonPath('meta.request_id', $requestId)
            ->assertDontSee('detalhe interno sensivel');
    }

    public function test_no_v1_routes_are_registered(): void
    {
        $temRotaV1 = collect(Route::getRoutes()->getRoutes())
            ->contains(fn ($route) => str_starts_with($route->uri(), 'api/v1'));

        $this->assertFalse($temRotaV1);
    }
}
