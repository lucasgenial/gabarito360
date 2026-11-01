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
            $usuario = \App\Support\TokenVinculo::resolver($request->user());
            $request->setUserResolver(fn () => $usuario);
        }
        // Identidade e resposta ao próprio convite precisam funcionar antes da seleção V2.
        if (!$request->is('api/v1/auth/vinculos', 'api/v1/auth/vinculos/selecionar', 'api/v1/auth/me', 'api/v1/auth/email', 'api/v1/auth/logout', 'api/v1/auth/convites-vinculo/*/consultar', 'api/v1/auth/convites-vinculo/*/responder')) RegimeVinculos::exigir($request->user());
        return $next($request);
    }
}
