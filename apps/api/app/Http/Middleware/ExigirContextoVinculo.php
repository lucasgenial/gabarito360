<?php

namespace App\Http\Middleware;

use App\Support\RegimeVinculos;
use Closure;
use Illuminate\Http\Request;

class ExigirContextoVinculo
{
    public function handle(Request $request, Closure $next)
    {
        // Identidade e resposta ao próprio convite precisam funcionar antes da seleção V2.
        if (!$request->is('api/v1/auth/me', 'api/v1/auth/email', 'api/v1/auth/logout', 'api/v1/auth/convites-vinculo/*/consultar', 'api/v1/auth/convites-vinculo/*/responder')) RegimeVinculos::exigir($request->user());
        return $next($request);
    }
}
