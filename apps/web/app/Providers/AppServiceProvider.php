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
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // @pode('cartao.enviar') ... @endpode — esconde ações que o perfil não tem (doc 03)
        Blade::if('pode', fn (string $acao) => Acesso::pode($acao));

        // Datas em português (nomes de mês e dia em translatedFormat)
        \Carbon\Carbon::setLocale(config('app.locale'));
    }
}
