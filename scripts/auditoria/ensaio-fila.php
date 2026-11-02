<?php
// Executado somente pelo job descartável auditoria-recuperacao. Nunca apontar ao servidor.
require __DIR__.'/../../apps/api/vendor/autoload.php';
$app = require __DIR__.'/../../apps/api/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Jobs\GravarAuditoria;
use Illuminate\Support\Facades\{DB, Queue, Redis};

function conferir(bool $ok, string $codigo): void { if (!$ok) throw new RuntimeException($codigo); }
try {
    conferir(getenv('G360_AUDITORIA_ENSAIO') === '1' && app()->environment('testing')
        && config('database.connections.mariadb.database') === 'g360_auditoria_ci'
        && config('database.connections.mariadb.host') === '127.0.0.1'
        && config('database.redis.default.host') === '127.0.0.1', 'ambiente_nao_descartavel');
    $fase=$argv[1] ?? '';
    $evento=fn($n)=>['id'=>sprintf('00000000-0000-4000-8000-%012d',$n),'mes'=>'2026-11',
        'ocorrido_em'=>'2026-11-01 12:00:00.000000','metodo'=>'GET','rota'=>'api/v1/alunos/{id}',
        'usuario_real_id'=>1,'referencias'=>'{"id":"1"}','status_http'=>200,'resultado'=>'sucesso'];
    $enviar=fn($n)=>app(Illuminate\Contracts\Bus\Dispatcher::class)->dispatch((new GravarAuditoria($evento($n)))->onConnection('redis'));
    $consumir=function () { $j=Queue::connection('redis')->pop('baixa'); conferir($j!==null,'job_ausente'); $j->fire(); };
    if ($fase === 'preparar') {
        DB::unprepared("CREATE USER 'au1_writer_ci'@'%' IDENTIFIED BY 'senha-ficticia-ci'");
        DB::unprepared("GRANT SELECT, INSERT ON g360_auditoria_ci.auditoria_eventos TO 'au1_writer_ci'@'%'");
        $enviar(1);
        $payload=Redis::connection()->lrange('queues:baixa',0,-1);
        conferir(count($payload)===1 && !str_contains($payload[0],'usuario_real_id'),'payload_nao_cifrado');
        $consumir(); $enviar(1); $consumir();
        conferir(DB::table('auditoria_eventos')->count()===1,'reentrega_duplicada');
        $restrito=DB::connection('auditoria_gravacao');
        foreach (["UPDATE auditoria_eventos SET resultado='erro'", 'DELETE FROM auditoria_eventos',
            'ALTER TABLE auditoria_eventos ADD COLUMN indevida INT'] as $sql) {
            $negado=false;
            try { $restrito->statement($sql); }
            catch (Illuminate\Database\QueryException $e) { $negado=(int)($e->errorInfo[1]??0)===1142; }
            conferir($negado,'privilegio_excessivo');
        }
        // Simula banco de auditoria indisponível, sem fallback ao usuário geral.
        $enviar(2);
        config(['database.connections.auditoria_gravacao.port'=>1]); DB::purge('auditoria_gravacao');
        $j=Queue::connection('redis')->pop('baixa'); $falhou=false;
        try { $j->fire(); } catch (Throwable) { $falhou=true; $j->release(0); }
        conferir($falhou && DB::table('auditoria_eventos')->count()===1,'falha_banco_nao_isolada');
        config(['database.connections.auditoria_gravacao.port'=>3306]); DB::purge('auditoria_gravacao');
        $consumir();
        conferir(DB::table('auditoria_eventos')->count()===2,'retry_nao_recuperado');
        $enviar(3); Redis::connection()->command('save');
        echo json_encode(['fase'=>$fase,'payload_cifrado'=>true,'privilegios_restritos'=>true,'retry_recuperado'=>true,'snapshot_com_pendencia'=>true]).PHP_EOL;
    } elseif ($fase === 'indisponivel') {
        config(['auditoria.ativa'=>true]);
        $request=Illuminate\Http\Request::create('/api/v1/alunos','GET',[],[],[],['HTTP_ACCEPT'=>'application/json']);
        $kernel=app(Illuminate\Contracts\Http\Kernel::class);
        $response=$kernel->handle($request); $kernel->terminate($request,$response);
        conferir($response->getStatusCode()===401,'falha_fila_alterou_resposta');
        echo json_encode(['fase'=>$fase,'resposta_preservada'=>true]).PHP_EOL;
    } elseif ($fase === 'recuperar') {
        conferir(Queue::connection('redis')->size('baixa')===1,'snapshot_perdeu_job');
        $consumir(); $enviar(3); $consumir();
        conferir(DB::table('auditoria_eventos')->count()===3 && Queue::connection('redis')->size('baixa')===0,'recuperacao_divergente');
        echo json_encode(['fase'=>$fase,'snapshot_recuperado'=>true,'eventos'=>3,'fila_vazia'=>true,'sem_duplicacao'=>true]).PHP_EOL;
    } else throw new RuntimeException('fase_invalida');
} catch (Throwable $e) {
    // Não imprimir conexão, credencial, payload ou exceção livre.
    fwrite(STDERR,'Ensaio de auditoria falhou: '.get_class($e).PHP_EOL); exit(1);
}
