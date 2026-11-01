<?php

namespace App\Http\Middleware;

use App\Support\RegimeVinculos;
use Closure;
use Illuminate\Http\Request;

class ExigirContextoVinculo
{
    public function handle(Request $request, Closure $next)
    {
        // O token persistido define o contexto; IDs em payload/header não o substituem.
        if (!$request->is('api/v1/auth/logout')) {
            $usuario = \App\Support\TokenInterno::resolver(\App\Support\TokenVinculo::resolver($request->user()));
            $request->setUserResolver(fn () => $usuario);
            if ($request->is('api/v1/acessos-internos', 'api/v1/acessos-internos/*')) {
                app(\App\Services\GestaoAcessosInternos::class)->autorizar($usuario);
                return $next($request);
            }
            if ($request->is('api/v1/clientes', 'api/v1/clientes/*')) {
                \App\Http\Controllers\Api\ClienteController::autorizar($request);
                return $next($request);
            }
            // A área interna não pode alcançar rotas escolares nem responder convites.
            if (\App\Support\ContextoInterno::presente($usuario)) {
                abort_unless($request->is('api/v1/auth/me', 'api/v1/auth/email'), 403);
            }
        }
        // Identidade e resposta ao próprio convite precisam funcionar antes da seleção V2.
        if (!$request->is('api/v1/auth/acessos-internos', 'api/v1/auth/acessos-internos/selecionar', 'api/v1/auth/vinculos', 'api/v1/auth/vinculos/selecionar', 'api/v1/auth/me', 'api/v1/auth/email', 'api/v1/auth/logout', 'api/v1/auth/convites-vinculo/*/consultar', 'api/v1/auth/convites-vinculo/*/responder')) RegimeVinculos::exigir($request->user());
        return $next($request);
    }
}
