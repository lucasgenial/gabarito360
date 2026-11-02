<?php
// Quarto ciclo: pacote, evidência, decisões, staging e job existem antes do backup.
require __DIR__.'/../../apps/api/vendor/autoload.php';
$app=require __DIR__.'/../../apps/api/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
use App\Jobs\{GravarAuditoria,ProcessarCopiaResultados};
use App\Models\Usuario;
use App\Services\{AcervoResultados,LotesResultadosCopia,OutboxCopia,FilaExecucaoCopia};
use Carbon\CarbonImmutable;
use Illuminate\Contracts\Bus\Dispatcher;
use Illuminate\Support\Facades\{DB,Queue,Redis};

function conferirD3(bool $ok,string $codigo): void { if (!$ok) throw new DomainException($codigo); }
function hashD3($v): string { return hash('sha256',json_encode($v,JSON_THROW_ON_ERROR)); }
function origemD3(): string {
    return hashD3([DB::table('notas')->whereBetween('id',[4001,4020])->orderBy('id')->get(),
        DB::table('cartoes')->whereBetween('id',[4001,4020])->orderBy('id')->get(),
        DB::table('cartao_respostas')->whereBetween('cartao_id',[4001,4020])->orderBy('id')->get(),
        DB::table('provas')->find(4000),DB::table('gabaritos')->where('prova_id',4000)->first(),
        DB::table('gabarito_questoes')->whereIn('gabarito_id',DB::table('gabaritos')->where('prova_id',4000)->select('id'))->orderBy('id')->get(),
        DB::table('alunos')->whereBetween('id',[4001,4020])->orderBy('id')->get(),DB::table('turmas')->find(4000)]);
}
function destinoD3(): array { $r=[]; foreach (['notas','cartoes','alunos','provas'] as $t) $r[$t]=DB::table($t)->where('espaco_id',902)->count(); return $r; }
function manifestoD3(string $id): string {
    $r=['origem'=>origemD3(),'lote'=>DB::table('copias_lotes')->find($id)];
    foreach (['copias_resultados','copias_evidencias','copias_execucoes','copias_autorizacoes','copias_outbox','copias_itens'] as $t)
        $r[$t]=DB::table($t)->where('lote_id',$id)->orderBy(in_array($t,['copias_resultados','copias_execucoes'],true)?'lote_id':'id')->get();
    $r['decisoes']=DB::table('copias_resultados_decisoes')->whereIn('decisao_id',DB::table('copias_autorizacoes')->where('lote_id',$id)->select('id'))->orderBy('decisao_id')->get();
    $r['stage']=DB::table('copias_staging')->whereIn('item_id',DB::table('copias_itens')->where('lote_id',$id)->select('id'))->orderBy('item_id')->get();
    $r['fila']=Redis::connection()->lrange('queues:baixa',0,-1); return hashD3($r);
}
function consumirD3(): void { $j=Queue::connection('redis')->pop('baixa'); conferirD3($j!==null,'job_ausente'); $j->fire(); }
function auditarD3(): void {
    while (DB::table('copias_outbox')->whereNull('entregue_em')->exists()) {
        DB::table('copias_outbox')->whereNull('entregue_em')->update(['proxima_tentativa_em'=>now()]);
        $r=(new OutboxCopia)->publicar(); conferirD3($r['falhas']===0 && $r['enviados']>0,'outbox');
        for ($i=0;$i<$r['enviados'];$i++) consumirD3();
    }
}
try {
    conferirD3(getenv('GITHUB_ACTIONS')==='true' && getenv('G360_AUDITORIA_ENSAIO')==='1' && app()->environment('testing')
        && DB::connection()->getDatabaseName()==='g360_auditoria_ci' && config('database.connections.mariadb.host')==='127.0.0.1'
        && config('database.redis.default.host')==='127.0.0.1','ambiente_invalido');
    config(['copias.ativo'=>true,'copias.execucao_ativa'=>true,'copias.resultados_execucao_ativa'=>true,'copias.auditoria_entrega'=>true,
        'espacos.ativo'=>true,'espacos.pessoal'=>true,'vinculos.obrigatorio'=>true,'auditoria.connection'=>'redis']);
    $s=new LotesResultadosCopia; $a=Usuario::findOrFail(901)->noVinculo(901); $b=Usuario::findOrFail(902)->noVinculo(902);
    $fase=$argv[1]??''; $sentinela='00000000-0000-4000-8000-000000910001';
    if ($fase==='preparar') {
        conferirD3(Queue::connection('redis')->size('baixa')===0,'fila_inicial');
        DB::statement('CREATE TABLE t5_d3_controle (id INT PRIMARY KEY, dados LONGTEXT NOT NULL)');
        DB::table('turmas')->insert(['id'=>4000,'espaco_id'=>901,'espaco_tipo'=>'pessoal','nome'=>'Turma fictícia D3','serie'=>'3','turno'=>'manha','ano_letivo'=>2026]);
        DB::table('provas')->insert(['id'=>4000,'espaco_id'=>901,'espaco_tipo'=>'pessoal','criado_por'=>901,
            'titulo'=>'Material fictício D3','disciplina'=>'Matemática','serie'=>'3','num_questoes'=>100,'num_alternativas'=>4]);
        DB::table('prova_turmas')->insert(['prova_id'=>4000,'turma_id'=>4000,'espaco_id'=>901]);
        $g=DB::table('gabaritos')->insertGetId(['prova_id'=>4000,'espaco_id'=>901]);
        $qs=[]; for ($q=1;$q<=100;$q++) $qs[]=['gabarito_id'=>$g,'espaco_id'=>901,'numero_questao'=>$q,'alternativa'=>'A'];
        DB::table('gabarito_questoes')->insert($qs);
        $ids=range(4001,4020);
        foreach ($ids as $id) {
            DB::table('alunos')->insert(['id'=>$id,'espaco_id'=>901,'espaco_tipo'=>'pessoal','turma_id'=>4000,'nome'=>'RESULTADO-FICTICIO-PRIVADO','matricula'=>'D3-'.$id]);
            DB::table('cartoes')->insert(['id'=>$id,'prova_id'=>4000,'aluno_id'=>$id,'turma_aplicacao_id'=>4000,'espaco_id'=>901,'status'=>'lido','imagem_url'=>'EXCLUIR-IMAGEM-FICTICIA']);
            $rs=[]; for ($q=1;$q<=100;$q++) $rs[]=['cartao_id'=>$id,'espaco_id'=>901,'numero_questao'=>$q,'alternativa'=>'A','ambigua'=>false];
            DB::table('cartao_respostas')->insert($rs);
            DB::table('notas')->insert(['id'=>$id,'cartao_id'=>$id,'prova_id'=>4000,'aluno_id'=>$id,'turma_id'=>4000,'espaco_id'=>901,
                'acertos'=>100,'total_questoes'=>100,'nota_final'=>10,'status_aprovacao'=>'aprovado']);
        }
        $id=$s->criar($a,901,902,'00000000-0000-4000-8000-000000910002',$ids,['provas'=>[4000],'alunos'=>$ids,'turmas'=>[4000]]);
        $p=$s->previa($a,$id,'origem'); $hash=$p['conteudo_digest'];
        $s->revisarEvidencia($a,$id,$hash,'EVIDENCIA-FICTICIA-D3','Guarda contratual fictícia','Exceções documentadas',true,true,CarbonImmutable::now()->addDay());
        $s->aprovar($a,$id,'origem',$hash,true,CarbonImmutable::now()->addHour());
        $s->aprovar($b,$id,'destino',$hash,true,CarbonImmutable::now()->addHour());
        auditarD3(); $s->solicitarExecucao($b,$id); auditarD3(); conferirD3($s->executarPasso($id)==='preparado','stage');
        conferirD3(DB::table('acervo_resultados')->count()===0,'visibilidade_precoce');
        DB::table('copias_execucoes')->where('lote_id',$id)->update(['proxima_tentativa_em'=>now()]);
        conferirD3((new FilaExecucaoCopia)->publicar()===['enviados'=>1,'falhas'=>0],'publicador');
        $evento=['id'=>$sentinela,'mes'=>now()->utc()->format('Y-m'),'ocorrido_em'=>now()->utc()->format('Y-m-d H:i:s.u'),
            'metodo'=>'GET','rota'=>'api/v1/alunos/{id}','usuario_real_id'=>901,'referencias'=>'{"id":"901"}','status_http'=>200,'resultado'=>'sucesso'];
        app(Dispatcher::class)->dispatch((new GravarAuditoria($evento))->onConnection('redis'));
        conferirD3(Queue::connection('redis')->size('baixa')===2,'fila_snapshot');
        foreach (Redis::connection()->lrange('queues:baixa',0,-1) as $payload) conferirD3(!str_contains($payload,'loteId') && !str_contains($payload,'RESULTADO-FICTICIO'),'job_privado');
        DB::table('t5_d3_controle')->insert(['id'=>1,'dados'=>json_encode(['lote'=>$id,'origem'=>origemD3(),'destino'=>destinoD3(),
            'conteudo'=>$hash,'manifesto'=>manifestoD3($id)],JSON_THROW_ON_ERROR)]);
        echo json_encode(['fase'=>'t5-d3-preparado','resultados'=>20,'respostas'=>2000,'jobs_pendentes'=>2,'pacote_e_stage_cifrados'=>true]).PHP_EOL;
    } elseif ($fase==='recuperar') {
        $c=json_decode(DB::table('t5_d3_controle')->where('id',1)->value('dados'),true,32,JSON_THROW_ON_ERROR); $id=$c['lote'];
        conferirD3(hash_equals($c['manifesto'],manifestoD3($id)),'manifesto');
        $negado=false; try { DB::connection('auditoria_gravacao')->statement('DELETE FROM auditoria_eventos WHERE 1=0'); }
        catch (Illuminate\Database\QueryException $e) { $negado=(int)($e->errorInfo[1]??0)===1142; } conferirD3($negado,'grants');
        $inicio=hrtime(true); consumirD3(); $job=(hrtime(true)-$inicio)/1e9; consumirD3(); $espera=(hrtime(true)-$inicio)/1e9;
        conferirD3(DB::table('auditoria_eventos')->where('id',$sentinela)->exists(),'sentinela');
        (new ProcessarCopiaResultados($id))->handle(); // Replay antes do recibo final.
        conferirD3(DB::table('acervo_resultados')->count()===1 && DB::table('acervo_resultados_itens')->count()===20,'acervo');
        conferirD3(DB::table('registros_origem')->where('lote_id',$id)->count()===20,'mapas');
        auditarD3(); (new ProcessarCopiaResultados($id))->handle(); (new ProcessarCopiaResultados($id))->handle();
        conferirD3(DB::table('copias_lotes')->where('id',$id)->value('estado')==='concluido','conclusao');
        $acervo=DB::table('acervo_resultados')->where('lote_id',$id)->first(); $p=(new AcervoResultados)->consultar($b,902,$acervo->id);
        conferirD3(hash('sha256',json_encode($p['pacote'],JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES|JSON_THROW_ON_ERROR))===$c['conteudo'],'conteudo');
        conferirD3(!str_contains(json_encode($p),'EXCLUIR-IMAGEM'),'minimizacao');
        conferirD3(hash_equals($c['origem'],origemD3()) && $c['destino']===destinoD3(),'dados_operacionais_alterados');
        conferirD3(DB::table('copias_staging')->whereIn('item_id',DB::table('copias_itens')->where('lote_id',$id)->select('id'))->whereNotNull('conteudo_cifrado')->count()===0,'stage_final');
        conferirD3(Queue::connection('redis')->size('baixa')===0 && (new OutboxCopia)->confirmada($id),'recibos');
        echo json_encode(['fase'=>'t5-d3-recuperado','manifesto_confere'=>true,'resultados'=>20,'respostas'=>2000,'mapas'=>20,
            'job_segundos'=>round($job,3),'sentinela_segundos'=>round($espera,3),'sem_duplicacao'=>true,'somente_dados_ficticios'=>true]).PHP_EOL;
        conferirD3($job<20 && $espera<30,'orcamento_desempenho');
    } else throw new DomainException('fase_invalida');
} catch (Throwable $e) { fwrite(STDERR,'Ensaio T5-D3 falhou: '.($e instanceof DomainException?$e->getMessage():get_class($e)).PHP_EOL); exit(1); }
