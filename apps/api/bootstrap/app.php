<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->append(\App\Http\Middleware\ProtegerRespostas::class);
        $middleware->append(\App\Http\Middleware\RegistrarAuditoria::class);
        // A API não tem tela de login: sem autenticação responde 401, nunca redireciona
        $middleware->redirectGuestsTo(fn () => null);

        $middleware->alias([
            'contexto_vinculo'   => \App\Http\Middleware\ExigirContextoVinculo::class,
            'perfil'             => \App\Http\Middleware\CheckPerfil::class,
            'permissao'          => \App\Http\Middleware\CheckPermissao::class,
            'assinatura_ativa'   => \App\Http\Middleware\CheckAssinaturaAtiva::class,
            'senha_trocada'      => \App\Http\Middleware\SenhaTrocada::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*'),
        );
    })->create();
