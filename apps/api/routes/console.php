<?php

use App\Jobs\VerificarInadimplenciaJob;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::job(VerificarInadimplenciaJob::class)->dailyAt('03:00');
Schedule::command('g360:copias-auditoria')->everyMinute()->withoutOverlapping()
    ->when(fn () => (bool) config('copias.auditoria_entrega'));
Schedule::command('g360:copias-executar')->everyMinute()->withoutOverlapping()
    ->when(fn () => (bool) config('copias.execucao_ativa') && (bool) config('copias.ativo'));
