<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class Autenticado
{
    public function handle(Request $request, Closure $next): Response
    {
        if (!session('auth_token')) {
            return redirect()->route('login')->with('erro', 'Faça login para continuar.');
        }

        return $next($request);
    }
}
