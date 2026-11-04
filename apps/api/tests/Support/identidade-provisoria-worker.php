<?php

require __DIR__.'/../../vendor/autoload.php';
$a = require __DIR__.'/../../bootstrap/app.php';
$a->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
if (!app()->environment('testing') || !str_ends_with(Illuminate\Support\Facades\DB::connection()->getDatabaseName(), '_test')) exit(3);
config(['identidade.provisorios_ativos' => true]);
echo "READY\n"; flush();
echo json_encode(app(App\Services\PrepararIdentidadesProvisorias::class)->executar(true, 1), JSON_THROW_ON_ERROR);
