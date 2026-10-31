<?php

namespace App\Http\Middleware;

use App\Http\Responses\ApiResponse;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Arquivo: apps/api/app/Http/Middleware/SenhaTrocada.php
 * Função: Enquanto a troca obrigatória de senha estiver pendente, o token criado
 * no login só alcança os próprios dados, a troca e a saída. Sessões abertas antes
 * de o gestor marcar a troca seguem valendo: ela vale a partir do próximo login.
 */
class SenhaTrocada
{
    public const TOKEN_RESTRITO = 'troca-senha';

    public function handle(Request $request, Closure $next): Response
    {
        $usuario = $request->user();

        if ($usuario?->forcar_troca_senha
            && ($usuario->currentAccessToken()->name ?? null) === self::TOKEN_RESTRITO
            && !$request->is('api/v1/auth/me', 'api/v1/auth/logout')) {
            return ApiResponse::error('Troque sua senha para continuar.', [], 403);
        }

        return $next($request);
    }
}
