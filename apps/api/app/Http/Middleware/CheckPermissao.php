<?php

namespace App\Http\Middleware;

use App\Http\Responses\ApiResponse;
use App\Support\Permissao;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Arquivo: apps/api/app/Http/Middleware/CheckPermissao.php
 * Função: permissao:chave1,chave2 — passa se o usuário tiver QUALQUER uma das permissões
 *         efetivas (docs/03 v2). O escopo dos dados continua nos controllers (Escopo).
 */
class CheckPermissao
{
    public function handle(Request $request, Closure $next, string ...$chaves): Response
    {
        $usuario = $request->user();
        if (!$usuario) {
            return ApiResponse::unauthorized();
        }

        foreach ($chaves as $chave) {
            if (Permissao::pode($usuario, $chave)) {
                return $next($request);
            }
        }

        return ApiResponse::forbidden();
    }
}
