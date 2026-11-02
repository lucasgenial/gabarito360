<?php
// Exclusivo do CI descartável. Não aceita outro banco/host/ambiente.
require __DIR__.'/../../apps/api/vendor/autoload.php';
$app=require __DIR__.'/../../apps/api/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Jobs\{GravarAuditoria,ProcessarCopiaMaterial};
use App\Models\Usuario;
use App\Services\{LotesCopia,OutboxCopia,FilaExecucaoCopia};
use Carbon\CarbonImmutable;
use Illuminate\Contracts\Bus\Dispatcher;
use Illuminate\Support\Facades\{DB,Queue,Redis};

function validarB3(bool $ok, string $codigo): void { if (!$ok) throw new DomainException($codigo); }
function hashB3($dados): string { return hash('sha256',json_encode($dados,JSON_THROW_ON_ERROR)); }
function origemB3(): string {
    return hashB3([
        DB::table('provas')->whereBetween('id',[2000,2118])->orderBy('id')->get(),
        DB::table('gabaritos')->whereBetween('prova_id',[2000,2118])->orderBy('id')->get(),
        DB::table('gabarito_questoes')->whereIn('gabarito_id',DB::table('gabaritos')->whereBetween('prova_id',[2000,2118])->select('id'))->orderBy('id')->get(),
    ]);
}
function manifestoB3(array $lotes): string {
    $dados=['origem'=>origemB3()];
    foreach (['copias_lotes','copias_itens','copias_execucoes','copias_outbox','registros_origem'] as $t) {
        $chave=$t==='copias_lotes'?'id':'lote_id';
        $dados[$t]=DB::table($t)->whereIn($chave,$lotes)->orderBy($t==='copias_execucoes'?'lote_id':'id')->get();
    }
    $dados['staging']=DB::table('copias_staging')->whereIn('item_id',DB::table('copias_itens')->whereIn('lote_id',$lotes)->select('id'))->orderBy('item_id')->get();
    $dados['destino']=DB::table('provas')->whereIn('id',DB::table('registros_origem')->whereIn('lote_id',$lotes)->select('destino_id'))->orderBy('id')->get();
    $dados['redis']=Redis::connection()->lrange('queues:baixa',0,-1);
    return hashB3($dados);
}
function consumirB3(): float {
    $inicio=hrtime(true); $job=Queue::connection('redis')->pop('baixa');
    validarB3($job!==null,'job_ausente'); $job->fire();
    return (hrtime(true)-$inicio)/1e9;
}
function recibosB3(): void {
    $o=new OutboxCopia;
    while (DB::table('copias_outbox')->whereNull('entregue_em')->exists()) {
        DB::table('copias_outbox')->whereNull('entregue_em')->update(['proxima_tentativa_em'=>now()]);
        $r=$o->publicar(); validarB3($r['falhas']===0 && $r['enviados']>0,'publicacao_recibo');
        for ($i=0;$i<$r['enviados'];$i++) consumirB3();
    }
}
function sentinelaB3(int $n): string {
    $id=sprintf('00000000-0000-4000-8000-%012d',800000+$n);
    $e=['id'=>$id,'mes'=>now()->utc()->format('Y-m'),'ocorrido_em'=>now()->utc()->format('Y-m-d H:i:s.u'),
        'metodo'=>'GET','rota'=>'api/v1/alunos/{id}','usuario_real_id'=>901,'referencias'=>'{"id":"901"}',
        'status_http'=>200,'resultado'=>'sucesso'];
    app(Dispatcher::class)->dispatch((new GravarAuditoria($e))->onConnection('redis'));
    return $id;
}
try {
    validarB3(getenv('GITHUB_ACTIONS')==='true' && getenv('G360_AUDITORIA_ENSAIO')==='1' && app()->environment('testing')
        && DB::connection()->getDatabaseName()==='g360_auditoria_ci'
        && config('database.connections.mariadb.host')==='127.0.0.1'
        && config('database.redis.default.host')==='127.0.0.1','ambiente_invalido');
    config(['copias.ativo'=>true,'copias.execucao_ativa'=>true,'copias.auditoria_entrega'=>true,
        'espacos.ativo'=>true,'espacos.pessoal'=>true,'vinculos.obrigatorio'=>true,'auditoria.connection'=>'redis']);
    $s=new LotesCopia; $a=Usuario::findOrFail(901)->noVinculo(901); $b=Usuario::findOrFail(902)->noVinculo(902);
    $fase=$argv[1]??'';
    if ($fase==='preparar') {
        validarB3(Queue::connection('redis')->size('baixa')===0,'fila_inicial');
        DB::statement('CREATE TABLE t5_b3_controle (id INT PRIMARY KEY, dados LONGTEXT NOT NULL)');
        for ($id=2000;$id<=2118;$id++) {
            DB::table('provas')->insert(['id'=>$id,'espaco_id'=>901,'espaco_tipo'=>'pessoal','criado_por'=>901,
                'titulo'=>'Material fictício B3','disciplina'=>'Matemática','serie'=>'3','num_questoes'=>100,'num_alternativas'=>5]);
            $g=DB::table('gabaritos')->insertGetId(['prova_id'=>$id,'espaco_id'=>901]); $questoes=[];
            for ($q=1;$q<=100;$q++) $questoes[]=['gabarito_id'=>$g,'espaco_id'=>901,'numero_questao'=>$q,'alternativa'=>'A'];
            DB::table('gabarito_questoes')->insert($questoes);
        }
        $lotes=[];
        for ($n=0;$n<20;$n++) {
            $id=$s->criar($a,901,902,sprintf('00000000-0000-4000-8000-%012d',700000+$n),$n===0?range(2000,2099):[2099+$n]);
            $previa=$s->previaMaterial($a,$id); $s->revisarMaterial($a,$id,$previa['conteudo_digest'],true);
            $s->aprovar($a,$id,'origem',CarbonImmutable::now()->addDay());
            $s->aprovar($b,$id,'destino',CarbonImmutable::now()->addDay()); $lotes[]=$id;
        }
        recibosB3();
        foreach ($lotes as $id) validarB3($s->solicitarExecucao($b,$id)===$id,'intencao');
        recibosB3();
        (new ProcessarCopiaMaterial($lotes[0]))->handle(); recibosB3();
        $editado=DB::table('registros_origem')->where('lote_id',$lotes[0])->value('destino_id');
        DB::table('provas')->where('id',$editado)->update(['titulo'=>'Edição legítima fictícia']);
        validarB3($s->executarPasso($lotes[0])==='preparado','staging_antes_backup');
        DB::table('copias_execucoes')->whereIn('lote_id',$lotes)->update(['proxima_tentativa_em'=>now()]);
        validarB3((new FilaExecucaoCopia)->publicar()===['enviados'=>20,'falhas'=>0],'rajada');
        $sentinela=sentinelaB3(0);
        validarB3(Queue::connection('redis')->size('baixa')===21,'fila_antes_backup');
        foreach (Redis::connection()->lrange('queues:baixa',0,-1) as $p) validarB3(!str_contains($p,'loteId') && !str_contains($p,'Material fictício'),'payload_privado');
        $controle=['lotes'=>$lotes,'editado'=>$editado,'sentinela'=>$sentinela,'origem'=>origemB3(),'manifesto'=>manifestoB3($lotes)];
        DB::table('t5_b3_controle')->insert(['id'=>1,'dados'=>json_encode($controle,JSON_THROW_ON_ERROR)]);
        echo json_encode(['fase'=>'t5-b3-preparado','lotes'=>20,'provas'=>119,'questoes'=>11900,'jobs_pendentes'=>21]).PHP_EOL;
    } elseif ($fase==='recuperar') {
        $c=json_decode(DB::table('t5_b3_controle')->where('id',1)->value('dados'),true,32,JSON_THROW_ON_ERROR);
        $lotes=$c['lotes']; validarB3(hash_equals($c['manifesto'],manifestoB3($lotes)),'manifesto_restaurado');
        validarB3(DB::table('copias_itens')->whereIn('lote_id',$lotes)->where('estado','publicado')->count()===1
            && DB::table('copias_itens')->whereIn('lote_id',$lotes)->where('estado','preparado')->count()===1,'estado_parcial');
        $inicio=hrtime(true); $tempos=[]; $esperas=[];
        // O primeiro ciclo usa exatamente os jobs recuperados, sem republicar o snapshot.
        for ($ciclo=0;$ciclo<105;$ciclo++) {
            if ($ciclo===0) { $quantidade=20; $sentinela=$c['sentinela']; }
            else {
                DB::table('copias_execucoes')->whereIn('lote_id',$lotes)->update(['proxima_tentativa_em'=>now()]);
                $r=(new FilaExecucaoCopia)->publicar(); validarB3($r['falhas']===0,'fila_recuperada');
                $quantidade=$r['enviados']; if ($quantidade===0) break;
                $sentinela=sentinelaB3($ciclo);
            }
            $antes=hrtime(true);
            for ($i=0;$i<$quantidade;$i++) $tempos[]=consumirB3();
            consumirB3(); $esperas[]=(hrtime(true)-$antes)/1e9;
            validarB3(DB::table('auditoria_eventos')->where('id',$sentinela)->exists(),'sentinela_entregue');
            // Repetição após commit enquanto recibos estão pendentes não publica outra unidade.
            $mapas=DB::table('registros_origem')->whereIn('lote_id',$lotes)->count();
            (new ProcessarCopiaMaterial($lotes[0]))->handle();
            validarB3(DB::table('registros_origem')->whereIn('lote_id',$lotes)->count()===$mapas,'replay_duplicou');
            recibosB3();
            validarB3((hrtime(true)-$inicio)/1e9<600,'tempo_total_excedido');
        }
        $duracao=(hrtime(true)-$inicio)/1e9;
        validarB3(DB::table('copias_execucoes')->whereIn('lote_id',$lotes)->where('estado','concluido')->count()===20,'conclusao');
        $mapas=DB::table('registros_origem')->whereIn('lote_id',$lotes)->get();
        validarB3($mapas->count()===119 && $mapas->pluck('destino_id')->unique()->count()===119,'mapas');
        validarB3(hash_equals($c['origem'],origemB3()),'origem_alterada');
        validarB3(DB::table('provas')->where('id',$c['editado'])->value('titulo')==='Edição legítima fictícia','edicao_sobrescrita');
        $destinos=$mapas->pluck('destino_id');
        validarB3(DB::table('provas')->whereIn('id',$destinos)->where('espaco_id',902)->where('status','rascunho')->count()===119,'destinos');
        validarB3(DB::table('gabaritos')->whereIn('prova_id',$destinos)->where('espaco_id',902)->count()===119,'gabaritos');
        validarB3(DB::table('gabarito_questoes')->whereIn('gabarito_id',DB::table('gabaritos')->whereIn('prova_id',$destinos)->select('id'))->where('espaco_id',902)->count()===11900,'questoes');
        validarB3(DB::table('copias_staging')->whereIn('item_id',DB::table('copias_itens')->whereIn('lote_id',$lotes)->select('id'))->whereNotNull('conteudo_cifrado')->count()===0,'staging_final');
        validarB3(DB::table('copias_outbox')->whereNull('entregue_em')->count()===0 && Queue::connection('redis')->size('baixa')===0,'pendencias');
        sort($tempos); $max=max($tempos); $espera=max($esperas);
        $metricas=['fase'=>'t5-b3-recuperado','manifesto_confere'=>true,'lotes'=>20,'provas'=>119,'questoes'=>11900,
            'jobs_medidos'=>count($tempos),'duracao_segundos'=>round($duracao,3),'unidades_restantes_por_segundo'=>round(118/$duracao,3),
            'job_p95_segundos'=>round($tempos[(int)ceil(count($tempos)*.95)-1],3),'job_max_segundos'=>round($max,3),
            'sentinela_max_segundos'=>round($espera,3),'somente_dados_ficticios'=>true];
        echo json_encode($metricas,JSON_THROW_ON_ERROR).PHP_EOL;
        validarB3($max<20 && $espera<30 && $duracao<600,'orcamento_desempenho');
    } else throw new DomainException('fase_invalida');
} catch (Throwable $e) {
    // Apenas códigos definidos neste script; nunca detalhes de conexão, SQL ou conteúdo.
    fwrite(STDERR,'Ensaio T5-B3 falhou: '.($e instanceof DomainException?$e->getMessage():get_class($e)).PHP_EOL); exit(1);
}
