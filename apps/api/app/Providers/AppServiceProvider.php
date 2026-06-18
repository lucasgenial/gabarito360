<?php

namespace App\Providers;

use App\Services\Omr\OmrDriverInterface;
use App\Services\Omr\SimuladoOmrDriver;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(OmrDriverInterface::class, SimuladoOmrDriver::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
