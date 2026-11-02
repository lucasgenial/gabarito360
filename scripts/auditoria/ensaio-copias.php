<?php
// Somente CI descartável; complementa o ensaio AU1 sem tocar o servidor.
require __DIR__.'/../../apps/api/vendor/autoload.php';
$app=require __DIR__.'/../../apps/api/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Usuario;
use App\Services\{LotesCopia,OutboxCopia};
use App\Jobs\GravarAuditoria;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\{DB,Queue,Redis};

function exigir(bool $ok): void { if (!$ok) throw new RuntimeException('Ensaio T5 divergente.'); }
try {
    exigir(getenv('G360_AUDITORIA_ENSAIO')==='1' && app()->environment('testing')
        && DB::connection()->getDatabaseName()==='g360_auditoria_ci'
        && config('database.connections.mariadb.host')==='127.0.0.1'
        && config('database.redis.default.host')==='127.0.0.1');
    config(['copias.ativo'=>true,'copias.auditoria_entrega'=>true,'espacos.ativo'=>true,'espacos.pessoal'=>true,
        'vinculos.obrigatorio'=>true,'auditoria.connection'=>'redis']);
    $fase=$argv[1] ?? ''; $chave='00000000-0000-4000-8000-000000009001'; $outbox=new OutboxCopia;
    if ($fase==='preparar') {
        foreach ([901,902] as $id) {
            Usuario::forceCreate(['id'=>$id,'perfil'=>'professor','nome'=>'Fictício','email'=>"t5-ci-$id@example.test",
                'cpf'=>sprintf('%011d',$id),'password'=>'ficticio','ativo'=>true]);
            DB::table('clientes')->insert(['id'=>$id,'tipo'=>'professor','nome'=>'Fictício','modalidade'=>'interno','situacao'=>'ativo']);
            DB::table('espacos')->insert(['id'=>$id,'cliente_id'=>$id,'tipo'=>'pessoal','nome'=>'Pessoal','situacao'=>'ativo','usuario_proprietario_id'=>$id]);
            DB::table('usuario_vinculos')->insert(['id'=>$id,'usuario_id'=>$id,'espaco_id'=>$id,'perfil'=>'professor_autonomo',
                'situacao'=>'ativo','inicio_em'=>'2025-01-01','versao_autorizacao'=>1]);
        }
        DB::table('provas')->insert(['id'=>901,'espaco_id'=>901,'espaco_tipo'=>'pessoal','criado_por'=>901,
            'titulo'=>'Fictício','disciplina'=>'Matemática','serie'=>'3','num_questoes'=>1,'num_alternativas'=>4]);
        DB::table('gabaritos')->insert(['id'=>901,'prova_id'=>901,'espaco_id'=>901]);
        DB::table('gabarito_questoes')->insert(['id'=>901,'gabarito_id'=>901,'espaco_id'=>901,'numero_questao'=>1,'alternativa'=>'A']);
        $s=new LotesCopia; $a=Usuario::find(901)->noVinculo(901); $b=Usuario::find(902)->noVinculo(902);
        $id=$s->criar($a,901,902,$chave,[901]);
        $previa=$s->previaMaterial($a,$id); $s->revisarMaterial($a,$id,$previa['conteudo_digest'],true);
        $s->aprovar($a,$id,'origem',CarbonImmutable::now()->addDay());
        $s->aprovar($b,$id,'destino',CarbonImmutable::now()->addDay());
        exigir(DB::table('copias_outbox')->count()===4 && !$outbox->confirmada($id));
    } elseif ($fase==='indisponivel') {
        $r=$outbox->publicar();
        exigir($r===['enviados'=>0,'falhas'=>4] && DB::table('copias_outbox')->whereNull('entregue_em')->count()===4);
    } elseif ($fase==='recuperar') {
        $id=DB::table('copias_lotes')->where('chave',$chave)->value('id');
        exigir($id && DB::table('copias_outbox')->where('erro_tecnico','fila_indisponivel')->count()===4);
        $recibo=DB::table('copias_outbox')->first(); $porta=config('database.connections.auditoria_gravacao.port');
        config(['database.connections.auditoria_gravacao.port'=>1]); DB::purge('auditoria_gravacao');
        $falhou=false; try { $outbox->entregar($recibo->id); } catch (RuntimeException) { $falhou=true; }
        exigir($falhou && DB::table('copias_outbox')->whereNull('entregue_em')->count()===4);
        config(['database.connections.auditoria_gravacao.port'=>$porta]); DB::purge('auditoria_gravacao');
        // Commit remoto sem confirmação local, antes de republicar a outbox restaurada.
        (new GravarAuditoria(json_decode($recibo->evento,true,32,JSON_THROW_ON_ERROR)))->handle();
        DB::table('copias_outbox')->update(['proxima_tentativa_em'=>now()]);
        exigir($outbox->publicar()===['enviados'=>4,'falhas'=>0]);
        $payloads=Redis::connection()->lrange('queues:baixa',0,-1);
        exigir(count($payloads)===4);
        foreach ($payloads as $payload) exigir(!str_contains($payload,'reciboId') && !str_contains($payload,'referencias'));
        for ($i=0;$i<4;$i++) { $job=Queue::connection('redis')->pop('baixa'); exigir($job!==null); $job->fire(); }
        $outbox->entregar($recibo->id);
        exigir($outbox->confirmada($id) && DB::table('auditoria_eventos')->count()===7
            && DB::table('copias_outbox')->whereNull('entregue_em')->count()===0 && Queue::connection('redis')->size('baixa')===0);
    } else throw new RuntimeException('Fase inválida.');
    echo json_encode(['fase'=>'t5-'.$fase,'validado'=>true,'sem_copia'=>true]).PHP_EOL;
} catch (Throwable) { fwrite(STDERR,"Ensaio T5 falhou; detalhes não expostos.\n"); exit(1); }
