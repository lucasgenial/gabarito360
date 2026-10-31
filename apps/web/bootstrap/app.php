<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->append(\App\Http\Middleware\ProtegerRespostas::class);
        $middleware->alias([
            'autenticado' => \App\Http\Middleware\Autenticado::class,
        ]);
        $middleware->redirectGuestsTo('/login');
        // Produção roda atrás do Cloudflare Tunnel (https termina no Cloudflare e chega aqui em http).
        // Confiar no X-Forwarded-Proto faz o Laravel gerar URLs https; sem isso o navegador
        // bloqueia formulários/fetch para http (o "Failed to fetch" no envio da foto).
        $middleware->trustProxies(at: '*');
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->dontFlash(['senha_atual', 'cf-turnstile-response']);
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*'),
        );
    })->create();
