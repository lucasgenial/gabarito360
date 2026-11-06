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
        $middleware->prepend(\App\Http\Middleware\RecusarCredenciaisNaUrl::class);
        $middleware->append(\App\Http\Middleware\ProtegerRespostas::class);
        $middleware->web(append: [
            \App\Http\Middleware\SerializarSessao::class,
            \App\Http\Middleware\RevisaoAcesso::class,
            \App\Http\Middleware\ExigirSessaoIntegra::class,
            \App\Http\Middleware\RespostaNavegacao::class,
            \App\Http\Middleware\RecursoNavegacao::class,
        ]);
        $middleware->prependToPriorityList(\Illuminate\Session\Middleware\StartSession::class, \App\Http\Middleware\SerializarSessao::class);
        $middleware->appendToPriorityList(\Illuminate\Session\Middleware\StartSession::class, \App\Http\Middleware\RevisaoAcesso::class);
        $middleware->appendToPriorityList(\Illuminate\Foundation\Http\Middleware\ValidateCsrfToken::class, \App\Http\Middleware\ExigirSessaoIntegra::class);
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
        $exceptions->respond(function (\Symfony\Component\HttpFoundation\Response $response, \Throwable $e, Request $request) {
            return $response->isRedirection() ? app(\App\Http\Middleware\RespostaNavegacao::class)->converter($request,$response) : $response;
        });
        $exceptions->dontFlash(['senha_atual', 'cf-turnstile-response', 'token', 'codigo', 'referencia', '_revisao','_recurso','_retorno_tela','_hidratar']);
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );
    })->create();
