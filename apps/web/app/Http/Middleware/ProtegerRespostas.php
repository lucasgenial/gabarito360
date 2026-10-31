<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class ProtegerRespostas
{
    public function handle(Request $request, Closure $next)
    {
        $response = $next($request);
        $response->headers->set('Referrer-Policy', 'no-referrer');
        $response->headers->set('Cache-Control', 'private, no-store');
        return $response;
    }
}
