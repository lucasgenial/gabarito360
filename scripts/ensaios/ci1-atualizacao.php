<?php
/** Mutação fictícia somente no clone isolado9120, nunca na VM de homologação ativa. */
declare(strict_types=1);
use App\Models\Usuario;
use App\Services\{CatalogoSelecao,ConsultarCatalogoInstituicoes,ManutencaoCatalogo,PrepararCatalogoInstituicoes,PublicarCatalogoInstituicoes,ReferenciasInstituicoesPessoais};
use Illuminate\Support\Facades\{DB,Hash};

$dir='/root/g360-ci1-f-20261103';
if (PHP_SAPI!=='cli' || getenv('G360_CI1_CLONE')!=='9120' || gethostname()!=='g360-homologa'
    || trim((string)@file_get_contents($dir.'/clone-9120'))!=='9120') throw new RuntimeException('Clone explícito obrigatório.');
foreach (glob('/sys/class/net/*/operstate') as $f) if (basename(dirname($f))!=='lo' && trim(file_get_contents($f))!=='down') throw new RuntimeException('Interface deve permanecer desconectada.');
require '/srv/gabarito360/current/apps/api/vendor/autoload.php';
$app=require '/srv/gabarito360/current/apps/api/bootstrap/app.php';$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
if (!app()->environment('staging') || DB::connection()->getDatabaseName()!=='gabarito360_homolog_snapshot_20261103') throw new RuntimeException('Banco incorreto.');
function manifesto(): array {
    $r=[];
    foreach(['alunos','turmas','provas','prova_turmas','gabaritos','gabarito_questoes','cartoes','cartao_respostas','notas'] as $t){
        $rows=DB::table($t)->get()->map(function($v){$v=(array)$v;ksort($v);return json_encode($v,JSON_THROW_ON_ERROR);})->all();sort($rows,SORT_STRING);
        $r[$t]=['total'=>count($rows),'sha256'=>hash('sha256',implode("\n",$rows))];
    }return $r;
}
if (($argv[1]??'')==='preparar') {
    if (is_file($dir.'/atores.json')) throw new RuntimeException('Preparação já existe.');
    file_put_contents($dir.'/academico-antes.json',json_encode(manifesto(),JSON_THROW_ON_ERROR));
    $usuarios=[];
    foreach([1,2] as $n){
        $u=Usuario::forceCreate(['nome'=>'ENSAIO CI1-F '.$n,'perfil'=>'professor','email'=>"ci1-f-$n@example.test",'cpf'=>'0000000900'.$n,
            'password'=>Hash::make('Ci1F-Ficticio-2026!'),'ativo'=>true,'forcar_troca_senha'=>false]);
        $c=DB::table('clientes')->insertGetId(['tipo'=>'professor','nome'=>'ENSAIO CI1-F '.$n,'modalidade'=>'interno','situacao'=>'ativo']);
        $e=DB::table('espacos')->insertGetId(['cliente_id'=>$c,'tipo'=>'pessoal','nome'=>'ENSAIO CI1-F '.$n,'situacao'=>'ativo','usuario_proprietario_id'=>$u->id]);
        $v=DB::table('usuario_vinculos')->insertGetId(['usuario_id'=>$u->id,'espaco_id'=>$e,'perfil'=>'professor_autonomo','situacao'=>'ativo','inicio_em'=>now()->subDay()]);
        $a=$n===1?DB::table('usuario_acessos_internos')->insertGetId(['usuario_id'=>$u->id,'perfil'=>'suporte','situacao'=>'ativo','inicio_em'=>now()->subDay(),'versao_autorizacao'=>1]):null;
        $usuarios[]=['usuario'=>$u->id,'vinculo'=>$v,'interno'=>$a];
    }
    file_put_contents($dir.'/atores.json',json_encode($usuarios,JSON_THROW_ON_ERROR));
    echo json_encode(['atores_ficticios'=>2,'apenas_clone'=>true]),PHP_EOL;exit;
}
if (($argv[1]??'')!=='atualizar') throw new RuntimeException('Modo preparar/atualizar obrigatório.');
$atores=json_decode(file_get_contents($dir.'/atores.json'),true,512,JSON_THROW_ON_ERROR);
$u=Usuario::findOrFail($atores[0]['usuario']);$pessoal=$u->noVinculo($atores[0]['vinculo']);$interno=$u->noAcessoInterno($atores[0]['interno'],1);
$consulta=app(ConsultarCatalogoInstituicoes::class);$refs=app(ReferenciasInstituicoesPessoais::class);
$e=app(CatalogoSelecao::class)->entrada($consulta->buscar(['namespace'=>'inep_escola','codigo'=>'29428254'])->items()[0]->unidade_id);
$ref=$refs->selecionar($pessoal,$e->unidade_id,$e->edicao_id);
$historico=DB::table('referencias_instituicoes_pessoais')->where('id',$ref['id'])->first();
$anterior=DB::table('catalogo_fontes')->where('nome','basica')->value('publicacao_id');
$antesIds=DB::table('catalogo_entradas')->where('publicacao_id',$anterior)->orderBy('unidade_id')->pluck('unidade_id')->all();
$nomeManual='ENSAIO FICTICIO CI1-F AJUSTE MANUAL';
$manual=app(ManutencaoCatalogo::class)->gravar($interno,['cadastro'=>['nome'=>$nomeManual,'sigla'=>'ECF','tipo'=>'escola','namespace'=>'inep_escola','codigo'=>'29428254',
    'uf'=>'BA','municipio_ibge'=>'2925303','dependencia'=>'estadual','site'=>'https://example.test/ensaio'],
    'situacao'=>'1','motivo'=>'ENSAIO FICTICIO exclusivo no clone9120','conferencia'=>true],$e->unidade_id,$e->edicao_id);
$base='/home/g360/catalogo-fontes';$arquivo=$dir.'/basica-2025-ENSAIO.csv';
$entrada=fopen($base.'/escolas-2025-v2-oficial.csv','rb');$saida=fopen($arquivo,'wb');
$cab=fgetcsv($entrada,262144,';','"','');$pos=array_flip($cab);$colunas=App\Support\Catalogo\LeitorInstituicoes::COLUNAS['basica'];
fputcsv($saida,$colunas,';','"','');$alterados=0;
while(($linha=fgetcsv($entrada,262144,';','"',''))!==false){
    $r=[];foreach($colunas as $coluna)$r[$coluna]=$linha[$pos[$coluna]];
    if($r['CO_ENTIDADE']==='29428254'){$r['NO_ENTIDADE']='ENSAIO FICTICIO CI1-F FONTE REVISADA';$alterados++;}
    fputcsv($saida,array_values($r),';','"','');
}fclose($entrada);fclose($saida);if($alterados!==1)throw new RuntimeException('Alvo público não único.');
$args=['arquivo'=>$arquivo,'fonte'=>'basica','ano'=>2025,'ufs'=>$base.'/ibge-ufs-20261103.json','municipios'=>$base.'/ibge-municipios-20261103.json',
    'sha256'=>hash_file('sha256',$arquivo),'encoding'=>'Windows-1252'];
$inicio=microtime(true);$prep=app(PrepararCatalogoInstituicoes::class);
$parcial=$prep->executar(...($args+['maxLotes'=>5]));
if(DB::table('catalogo_fontes')->where('nome','basica')->value('publicacao_id')!==$anterior)throw new RuntimeException('Staging trocou ponteiro.');
$pronto=$prep->executar(...$args);if($parcial['importacao_id']!==$pronto['importacao_id'])throw new RuntimeException('Retomada mudou identidade.');
$publicar=app(PublicarCatalogoInstituicoes::class);
$pausa=$publicar->executar($pronto['importacao_id'],$args['ufs'],maxLotes:5,substituir:$anterior,motivo:'ENSAIO FICTICIO nacional no clone9120');
if(DB::table('catalogo_fontes')->where('nome','basica')->value('publicacao_id')!==$anterior)throw new RuntimeException('Projeção parcial exposta.');
$fim=$publicar->executar($pronto['importacao_id'],$args['ufs'],substituir:$anterior,motivo:'ENSAIO FICTICIO nacional no clone9120');
$novo=$fim['publicacao_id'];$novosIds=DB::table('catalogo_entradas')->where('publicacao_id',$novo)->orderBy('unidade_id')->pluck('unidade_id')->all();
if($antesIds!==$novosIds || count($novosIds)!==214192)throw new RuntimeException('Identidades oficiais mudaram.');
if(app(CatalogoSelecao::class)->entrada($e->unidade_id)->nome!==$nomeManual)throw new RuntimeException('Override perdido.');
if($historico!=DB::table('referencias_instituicoes_pessoais')->where('id',$ref['id'])->first())throw new RuntimeException('Snapshot pessoal reescrito.');
$antiga=DB::table('catalogo_publicacoes')->where('id',$anterior)->first();
App\Support\Catalogo\PoliticaEdicaoCatalogo::conferir(2025,$antiga,$anterior,'Ensaio');
$recusada=false;try{App\Support\Catalogo\PoliticaEdicaoCatalogo::conferir(2024,DB::table('catalogo_publicacoes')->where('id',$novo)->first(),null,null);}catch(RuntimeException){$recusada=true;}
if(!$recusada)throw new RuntimeException('Ano anterior aceito.');
$repetir=$publicar->executar($antiga->importacao_id,$args['ufs']);
if(DB::table('catalogo_fontes')->where('nome','basica')->value('publicacao_id')!==$novo)throw new RuntimeException('Edição antiga reativada.');
$antes=json_decode(file_get_contents($dir.'/academico-antes.json'),true,512,JSON_THROW_ON_ERROR);$depois=manifesto();
if($antes!==$depois)throw new RuntimeException('Acervo acadêmico alterado.');
$r=['clone'=>9120,'total_oficial'=>count($novosIds),'ids_preservados'=>true,'pausa_staging_cursor'=>$parcial['cursor'],
    'pausa_publicacao_cursor'=>$pausa['cursor'],'edicao_anterior_preservada'=>true,'override_preservado'=>true,'snapshot_pessoal_preservado'=>true,
    'ano_anterior_recusado'=>$recusada,'repeticao_antiga_nao_reativa'=>true,'academico_identico'=>true,'tabelas_academicas'=>count($depois),
    'duracao_s'=>round(microtime(true)-$inicio,2),'pico_memoria_bytes'=>memory_get_peak_usage(true),'fonte_derivada_ficticia'=>true];
file_put_contents($dir.'/atualizacao.json',json_encode($r,JSON_PRETTY_PRINT|JSON_THROW_ON_ERROR));echo json_encode($r),PHP_EOL;
