<?php

namespace App\Http\Middleware\Api\V2;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureOrganizationalScope
{
    /**
     * Placeholder da fundacao B0.
     *
     * A resolucao completa do escopo organizacional (nucleo -> escola ->
     * turma) sera implementada em B1, junto dos primeiros recursos
     * autenticados. O middleware ja fica registrado no pipeline /api/v2 para
     * que B1+ apenas preencham a logica, sem alterar routes/api.php.
     *
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        return $next($request);
    }
}
