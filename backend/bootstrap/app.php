<?php

use App\Http\Middleware\RequestId;
use App\Support\ApiResponse;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->prepend(RequestId::class);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request): bool => $request->is('api/*') || $request->expectsJson()
        );

        $exceptions->context(function (): array {
            if (! app()->bound('request')) {
                return [];
            }

            $requestId = request()->attributes->get('request_id');

            return is_string($requestId) ? ['request_id' => $requestId] : [];
        });

        $exceptions->renderable(function (Throwable $exception, Request $request): ?JsonResponse {
            if (! $request->is('api/*')) {
                return null;
            }

            if ($exception instanceof ValidationException) {
                return ApiResponse::error(
                    code: 'VALIDATION_ERROR',
                    message: 'Os dados informados sao invalidos.',
                    details: $exception->errors(),
                    status: 422,
                );
            }

            if ($exception instanceof AuthenticationException) {
                return ApiResponse::error('UNAUTHENTICATED', 'Autenticacao necessaria.', status: 401);
            }

            if ($exception instanceof AuthorizationException) {
                return ApiResponse::error('FORBIDDEN', 'Acao nao autorizada.', status: 403);
            }

            if ($exception instanceof MethodNotAllowedHttpException) {
                return ApiResponse::error('METHOD_NOT_ALLOWED', 'Metodo HTTP nao permitido.', status: 405);
            }

            if ($exception instanceof ModelNotFoundException || $exception instanceof NotFoundHttpException) {
                return ApiResponse::error('RESOURCE_NOT_FOUND', 'Recurso nao encontrado.', status: 404);
            }

            if ($exception instanceof HttpExceptionInterface) {
                return match ($exception->getStatusCode()) {
                    400 => ApiResponse::error('BAD_REQUEST', 'Requisicao invalida.', status: 400),
                    401 => ApiResponse::error('UNAUTHENTICATED', 'Autenticacao necessaria.', status: 401),
                    403 => ApiResponse::error('FORBIDDEN', 'Acao nao autorizada.', status: 403),
                    409 => ApiResponse::error('CONFLICT', 'Conflito ao processar a requisicao.', status: 409),
                    422 => ApiResponse::error('VALIDATION_ERROR', 'Os dados informados sao invalidos.', status: 422),
                    429 => ApiResponse::error('TOO_MANY_REQUESTS', 'Muitas requisicoes. Tente novamente mais tarde.', status: 429),
                    503 => ApiResponse::error('SERVICE_UNAVAILABLE', 'Servico temporariamente indisponivel.', status: 503),
                    default => ApiResponse::error('HTTP_ERROR', 'Nao foi possivel processar a requisicao.', status: $exception->getStatusCode()),
                };
            }

            return ApiResponse::error(
                code: 'INTERNAL_ERROR',
                message: 'Erro interno do servidor.',
                status: 500,
            );
        });
    })->create();
