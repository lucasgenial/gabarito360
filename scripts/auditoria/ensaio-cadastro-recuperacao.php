<?php
// Terceiro ciclo de CI: evidência, staging e job de cadastro já existem no backup.
require __DIR__.'/../../apps/api/vendor/autoload.php';
$app=require __DIR__.'/../../apps/api/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Jobs\{GravarAuditoria,ProcessarCopiaCadastro};
use App\Models\Usuario;
use App\Services\{LotesCadastroCopia,OutboxCopia,FilaExecucaoCopia};
use Carbon\CarbonImmutable;
use Illuminate\Contracts\Bus\Dispatcher;
use Illuminate\Support\Facades\{DB,Queue,Redis,Schema};

function conferirC3(bool $ok,string $codigo): void { if (!$ok) throw new DomainException($codigo); }
function hashC3($v): string { return hash('sha256',json_encode($v,JSON_THROW_ON_ERROR)); }
function origemC3(): string {
    return hashC3([DB::table('turmas')->where('id',3000)->first(),DB::table('alunos')->where('turma_id',3000)->orderBy('id')->get()]);
}
function manifestoC3(string $id): string {
    $r=['origem'=>origemC3(),'lote'=>DB::table('copias_lotes')->where('id',$id)->first()];
    foreach (['copias_cadastros','copias_evidencias','copias_execucoes','copias_autorizacoes','copias_outbox','copias_itens'] as $t) {
        $r[$t]=DB::table($t)->where('lote_id',$id)->orderBy(in_array($t,['copias_cadastros','copias_execucoes'],true)?'lote_id':'id')->get();
    }
    $r['stage']=DB::table('copias_staging')->whereIn('item_id',DB::table('copias_itens')->where('lote_id',$id)->select('id'))->orderBy('item_id')->get();
    $r['fila']=Redis::connection()->lrange('queues:baixa',0,-1); return hashC3($r);
}
function consumirC3(): void {
    $j=Queue::connection('redis')->pop('baixa'); conferirC3($j!==null,'job_ausente'); $j->fire();
}
function auditarC3(): void {
    while (DB::table('copias_outbox')->whereNull('entregue_em')->exists()) {
        DB::table('copias_outbox')->whereNull('entregue_em')->update(['proxima_tentativa_em'=>now()]);
        $r=(new OutboxCopia)->publicar(); conferirC3($r['falhas']===0 && $r['enviados']>0,'outbox');
        for ($i=0;$i<$r['enviados'];$i++) consumirC3();
    }
}
try {
    conferirC3(getenv('GITHUB_ACTIONS')==='true' && getenv('G360_AUDITORIA_ENSAIO')==='1' && app()->environment('testing')
        && DB::connection()->getDatabaseName()==='g360_auditoria_ci'
        && config('database.connections.mariadb.host')==='127.0.0.1'
        && config('database.redis.default.host')==='127.0.0.1','ambiente_invalido');
    config(['copias.ativo'=>true,'copias.execucao_ativa'=>true,'copias.cadastro_execucao_ativa'=>true,'copias.auditoria_entrega'=>true,
        'espacos.ativo'=>true,'espacos.pessoal'=>true,'vinculos.obrigatorio'=>true,'auditoria.connection'=>'redis']);
    $s=new LotesCadastroCopia; $a=Usuario::findOrFail(901)->noVinculo(901); $b=Usuario::findOrFail(902)->noVinculo(902);
    $fase=$argv[1]??''; $sentinela='00000000-0000-4000-8000-000000900001';
    if ($fase==='preparar') {
        conferirC3(Queue::connection('redis')->size('baixa')===0,'fila_inicial');
        // Ensaio pós-corte T2: remover somente a unicidade global legada no banco descartável.
        if (collect(Schema::getIndexes('alunos'))->contains('name','alunos_matricula_unique')) {
            Schema::table('alunos',function($t) { $t->unique(['espaco_id','matricula'],'t2_alunos_matricula'); $t->dropUnique('alunos_matricula_unique'); });
        }
        DB::statement('CREATE TABLE t5_c3_controle (id INT PRIMARY KEY, dados LONGTEXT NOT NULL)');
        DB::table('turmas')->insert(['id'=>3000,'espaco_id'=>901,'espaco_tipo'=>'pessoal','escola_id'=>null,
            'nome'=>'Turma fictícia C3','serie'=>'3','turno'=>'manha','ano_letivo'=>2026]);
        $ids=range(3001,3099);
        foreach ($ids as $id) DB::table('alunos')->insert(['id'=>$id,'espaco_id'=>901,'espaco_tipo'=>'pessoal','turma_id'=>3000,
            'nome'=>'CADASTRO-FICTICIO-PRIVADO','matricula'=>'C3-'.$id,'avatar_id'=>5]);
        $id=$s->criar($a,901,902,'00000000-0000-4000-8000-000000900002',[3000],$ids);
        $p=$s->previaOrigem($a,$id);
        $s->revisarEvidencia($a,$id,$p['conteudo_digest'],'EVIDENCIA-FICTICIA-RESTRITA-C3',true,CarbonImmutable::now()->addDay());
        $s->aprovar($a,$id,'origem',CarbonImmutable::now()->addHour());
        $r=$s->conciliar($b,$id); $s->aprovar($b,$id,'destino',CarbonImmutable::now()->addHour(),$r['digest']);
        auditarC3(); $s->solicitarExecucao($b,$id); auditarC3();
        conferirC3($s->executarPasso($id)==='preparado','staging');
        conferirC3(DB::table('alunos')->where('espaco_id',902)->count()===0,'visibilidade_precoce');
        $stage=DB::table('copias_staging')->whereIn('item_id',DB::table('copias_itens')->where('lote_id',$id)->select('id'))->first();
        conferirC3($stage && !str_contains($stage->conteudo_cifrado,'CADASTRO-FICTICIO'),'stage_privado');
        DB::table('copias_execucoes')->where('lote_id',$id)->update(['proxima_tentativa_em'=>now()]);
        conferirC3((new FilaExecucaoCopia)->publicar()===['enviados'=>1,'falhas'=>0],'publicador');
        $evento=['id'=>$sentinela,'mes'=>now()->utc()->format('Y-m'),'ocorrido_em'=>now()->utc()->format('Y-m-d H:i:s.u'),
            'metodo'=>'GET','rota'=>'api/v1/alunos/{id}','usuario_real_id'=>901,'referencias'=>'{"id":"901"}','status_http'=>200,'resultado'=>'sucesso'];
        app(Dispatcher::class)->dispatch((new GravarAuditoria($evento))->onConnection('redis'));
        conferirC3(Queue::connection('redis')->size('baixa')===2,'fila_snapshot');
        foreach (Redis::connection()->lrange('queues:baixa',0,-1) as $p) conferirC3(!str_contains($p,'loteId') && !str_contains($p,'CADASTRO-FICTICIO'),'job_privado');
        DB::table('t5_c3_controle')->insert(['id'=>1,'dados'=>json_encode(['lote'=>$id,'origem'=>origemC3(),'manifesto'=>manifestoC3($id)],JSON_THROW_ON_ERROR)]);
        echo json_encode(['fase'=>'t5-c3-preparado','turmas'=>1,'alunos'=>99,'jobs_pendentes'=>2,'staging_cifrado'=>true]).PHP_EOL;
    } elseif ($fase==='recuperar') {
        $c=json_decode(DB::table('t5_c3_controle')->where('id',1)->value('dados'),true,32,JSON_THROW_ON_ERROR); $id=$c['lote'];
        conferirC3(hash_equals($c['manifesto'],manifestoC3($id)),'manifesto');
        $negado=false;
        try { DB::connection('auditoria_gravacao')->statement('DELETE FROM auditoria_eventos WHERE 1=0'); }
        catch (Illuminate\Database\QueryException $e) { $negado=(int)($e->errorInfo[1]??0)===1142; }
        conferirC3($negado,'grants');
        $inicio=hrtime(true); consumirC3(); $job=(hrtime(true)-$inicio)/1e9;
        consumirC3(); $espera=(hrtime(true)-$inicio)/1e9;
        conferirC3(DB::table('auditoria_eventos')->where('id',$sentinela)->exists(),'sentinela');
        (new ProcessarCopiaCadastro($id))->handle(); // Repetir após commit, antes de confirmar recibos.
        conferirC3(DB::table('registros_origem')->where('lote_id',$id)->count()===100,'mapas');
        conferirC3(DB::table('copias_lotes')->where('id',$id)->value('estado')==='aguardando_recibo','estado');
        auditarC3(); (new ProcessarCopiaCadastro($id))->handle();
        conferirC3(DB::table('copias_lotes')->where('id',$id)->value('estado')==='concluido','conclusao');
        conferirC3(hash_equals($c['origem'],origemC3()),'origem_alterada');
        $turma=DB::table('turmas')->where('espaco_id',902)->first();
        conferirC3($turma && (int)$turma->id!==3000 && $turma->escola_id===null && (int)$turma->criado_por===902,'turma_destino');
        conferirC3(DB::table('alunos')->where('espaco_id',902)->where('turma_id',$turma->id)->count()===99,'dependencias');
        foreach (DB::table('registros_origem')->where('lote_id',$id)->where('entidade','aluno')->get() as $m) {
            $fonte=DB::table('alunos')->find($m->origem_id); $destino=DB::table('alunos')->find($m->destino_id);
            conferirC3($destino && (int)$fonte->id!==(int)$destino->id && $destino->nome===$fonte->nome
                && $destino->matricula===$fonte->matricula && $destino->avatar_id===$fonte->avatar_id && (int)$destino->criado_por===902,'conteudo');
            foreach (['cpf','cpf_hash','data_nascimento','genero','nome_responsavel','foto_path'] as $campo) conferirC3($destino->$campo===null,'minimizacao');
        }
        conferirC3(DB::table('copias_staging')->whereIn('item_id',DB::table('copias_itens')->where('lote_id',$id)->select('id'))->whereNotNull('conteudo_cifrado')->count()===0,'stage_final');
        conferirC3(Queue::connection('redis')->size('baixa')===0 && (new OutboxCopia)->confirmada($id),'recibos');
        echo json_encode(['fase'=>'t5-c3-recuperado','manifesto_confere'=>true,'turmas'=>1,'alunos'=>99,'mapas'=>100,
            'job_segundos'=>round($job,3),'sentinela_segundos'=>round($espera,3),'sem_duplicacao'=>true,'somente_dados_ficticios'=>true]).PHP_EOL;
        conferirC3($job<20 && $espera<30,'orcamento_desempenho');
    } else throw new DomainException('fase_invalida');
} catch (Throwable $e) {
    fwrite(STDERR,'Ensaio T5-C3 falhou: '.($e instanceof DomainException?$e->getMessage():get_class($e)).PHP_EOL); exit(1);
}
