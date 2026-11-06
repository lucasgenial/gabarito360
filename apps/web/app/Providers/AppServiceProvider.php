<?php

namespace App\Providers;

use App\Support\Acesso;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton('url', function ($app) {
            $routes=$app['router']->getRoutes();$app->instance('routes',$routes);
            return new \App\Routing\GeradorUrl($routes,$app->rebinding('request',function($app,$request){$app['url']->setRequest($request);}),$app['config']['app.asset_url']);
        });
        $this->app->extend('log', fn ($original, $app) => new \App\Logging\GerenciadorProtegido($app));
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // @pode('cartao.enviar') ... @endpode — esconde ações que o perfil não tem (doc 03)
        Blade::if('pode', fn (string $acao) => Acesso::pode($acao));
        \Illuminate\Support\Facades\View::composer('layouts.app',function ($view) {
            $view->with('opcoesTroca',!empty($view->getData()['navegacaoEstrutura'])
                ? ['opcoes'=>[],'disponivel'=>false,'erro'=>false]
                : app(\App\Support\OpcoesTrocaContexto::class)->obter());
        });

        // Datas em português (nomes de mês e dia em translatedFormat)
        \Carbon\Carbon::setLocale(config('app.locale'));
    }
}
