<?php
require __DIR__.'/../../vendor/autoload.php';
$app=require __DIR__.'/../../bootstrap/app.php';$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
use Illuminate\Support\Facades\DB;
if(app()->environment()!=='testing' || !str_ends_with(DB::connection()->getDatabaseName(),'_test')) exit(2);
config(['catalogo.interface_ativa'=>true,'espacos.ativo'=>true,'espacos.pessoal'=>true,'vinculos.obrigatorio'=>true]);
echo "READY\n";flush();
try {
    $r=app(App\Services\ReferenciasInstituicoesPessoais::class)->selecionar(App\Models\Usuario::findOrFail(1)->noVinculo(1),$argv[1],$argv[2]);
    echo json_encode($r,JSON_THROW_ON_ERROR).PHP_EOL;
} catch(Symfony\Component\HttpKernel\Exception\HttpExceptionInterface $e) { echo json_encode(['status'=>$e->getStatusCode()]).PHP_EOL; }
