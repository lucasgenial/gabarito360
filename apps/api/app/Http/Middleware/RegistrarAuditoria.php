<?php
namespace App\Http\Middleware;

use App\Support\Auditoria;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RegistrarAuditoria
{
    public function handle(Request $request, Closure $next) { return $next($request); }
    public function terminate(Request $request, Response $response): void { Auditoria::registrar($request, $response); }
}
