<?php

namespace App\Http\Middleware;

use App\Http\Responses\ApiResponse;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckPerfil
{
    public function handle(Request $request, Closure $next, string ...$perfis): Response
    {
        $usuario = $request->user();

        if (!$usuario) {
            return ApiResponse::unauthorized();
        }

        if (!in_array($usuario->perfil, $perfis)) {
            return ApiResponse::forbidden();
        }

        return $next($request);
    }
}
