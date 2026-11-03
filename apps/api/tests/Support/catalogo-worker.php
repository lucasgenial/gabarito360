<?php
require __DIR__.'/../../vendor/autoload.php';
$app=require __DIR__.'/../../bootstrap/app.php';$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
use Illuminate\Support\Facades\DB;
if(app()->environment()!=='testing' || !str_ends_with(DB::connection()->getDatabaseName(),'_test')) exit(2);
config(['catalogo.interface_ativa'=>true,'espacos.ativo'=>true,'espacos.pessoal'=>true,'vinculos.obrigatorio'=>true,'acessos_internos.ativo'=>true]);
echo "READY\n";flush();
try {
    if($argv[1]==='solicitar')$r=app(App\Services\SolicitacoesCatalogo::class)->criar(App\Models\Usuario::findOrFail(1)->noVinculo(1),['chave'=>$argv[2],'cadastro'=>['nome'=>'Instituto fictício concorrente','tipo'=>'instituto','uf'=>'BA','municipio_ibge'=>'2900001','dependencia'=>'privada']]);
    elseif($argv[1]==='manual')$r=app(App\Services\ManutencaoCatalogo::class)->gravar(App\Models\Usuario::findOrFail(2)->noAcessoInterno(2,1),['cadastro'=>['nome'=>'Escola fictícia concorrente','tipo'=>'escola','uf'=>'BA','municipio_ibge'=>'2900001','dependencia'=>'estadual','namespace'=>'inep_escola','codigo'=>'29999999','site'=>'https://example.test/conferencia'],'motivo'=>'Conferência fictícia concorrente','conferencia'=>true,'situacao'=>'1']);
    else $r=app(App\Services\ReferenciasInstituicoesPessoais::class)->selecionar(App\Models\Usuario::findOrFail(1)->noVinculo(1),$argv[1],$argv[2]);
    echo json_encode($r,JSON_THROW_ON_ERROR).PHP_EOL;
} catch(Symfony\Component\HttpKernel\Exception\HttpExceptionInterface $e) { echo json_encode(['status'=>$e->getStatusCode()]).PHP_EOL; }
