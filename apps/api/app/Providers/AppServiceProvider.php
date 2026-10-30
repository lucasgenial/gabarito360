<?php

namespace App\Providers;

use App\Services\Omr\OmrDriverInterface;
use App\Services\Omr\NativoOmrDriver;
use App\Services\Omr\SimuladoOmrDriver;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // OMR_DRIVER=nativo (padrão) usa visão computacional real; simulado é só para dev/testes.
        $this->app->bind(OmrDriverInterface::class, fn () => config('omr.driver') === 'simulado'
            ? new SimuladoOmrDriver()
            : new NativoOmrDriver());
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
