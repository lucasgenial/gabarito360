<?php
namespace App\Http\Middleware;

use App\Support\{ContextoWeb,SessaoWeb};
use Closure;
use Illuminate\Http\Request;

/** Após CSRF: uma requisição forjada nunca encerra o acesso autenticado. */
final class ExigirSessaoIntegra
{
    public function handle(Request $request, Closure $next)
    {
        if (ContextoWeb::identidade() && ContextoWeb::temSelecao() && !ContextoWeb::atual()) return SessaoWeb::encerrar($request);
        return $next($request);
    }
}
