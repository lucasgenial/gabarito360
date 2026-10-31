<?php

namespace App\Providers;

use App\Services\Omr\OmrDriverInterface;
use App\Services\Omr\NativoOmrDriver;
use App\Services\Omr\SimuladoOmrDriver;
use Illuminate\Mail\Events\MessageSent;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->extend('log', fn ($original, $app) => new \App\Logging\GerenciadorProtegido($app));
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
        $this->app->extend('queue.failer', function ($original, $app) {
            $config = $app['config']['queue.failed'];
            return $config['driver'] === 'database-uuids'
                ? new \App\Support\FalhasProtegidas($app['db'], $config['database'], $config['table'])
                : $original;
        });
        Event::listen(MessageSent::class, function (MessageSent $evento) {
            $modelo = $evento->message->getHeaders()->getHeaderBody('X-G360-Modelo');
            if (!$modelo) {
                return;
            }

            foreach ($evento->message->getTo() as $destino) {
                DB::table('emails_enviados')->insert([
                    'modelo'            => $modelo,
                    'destinatario_hash' => hash('sha256', mb_strtolower($destino->getAddress())),
                ]);
            }
        });
    }
}
