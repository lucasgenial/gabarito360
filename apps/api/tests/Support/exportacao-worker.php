<?php
require __DIR__.'/../../vendor/autoload.php';
$app=require __DIR__.'/../../bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();
if (!app()->environment('testing') || !str_ends_with(\Illuminate\Support\Facades\DB::connection()->getDatabaseName(),'_test')) exit(2);
config(['espacos.ativo'=>true,'espacos.pessoal'=>true,'vinculos.obrigatorio'=>true,'acessos_internos.ativo'=>true,
    'pedidos_titulares.ativo'=>true,'pedidos_titulares.autorizacoes_exportacao'=>true,
    'pedidos_titulares.geracao_exportacao'=>true,'pedidos_titulares.entrega_exportacao'=>true]);
$u=\App\Models\Usuario::find(2)->noAcessoInterno(2,1);
echo "READY\n";flush();
try {
    if ($argv[1]==='gerar') $r=app(\App\Services\GeracaoExportacaoTitular::class)->gerar($u,$argv[2],['versao'=>1,'chave'=>$argv[3]]);
    else {app(\App\Services\EntregaExportacaoTitular::class)->baixar($u,$argv[2]);$r=['status'=>200];}
    echo json_encode($r,JSON_THROW_ON_ERROR);
}catch(\Symfony\Component\HttpKernel\Exception\HttpException $e){echo json_encode(['status'=>$e->getStatusCode()]);}
