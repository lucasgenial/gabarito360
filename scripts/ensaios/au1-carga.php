<?php
// Auxiliar pontual: guarda identidade do clone, marcador, rede e banco antes de escrever.
$base='/home/lucasdev/projetos/gabarito360/apps/api';
if (!in_array(gethostname(),['g360-au1-app-origem','g360-au1-app-restaurado'],true) || !is_file('/etc/g360-au1-ensaio')) exit(2);
$rotas=json_decode(shell_exec('ip -j route'),true);
if (!is_array($rotas) || count($rotas)!==1 || $rotas[0]['dst']!=='10.0.0.0/24') exit(2);
require $base.'/vendor/autoload.php';
$app=require $base.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
use Illuminate\Support\Facades\{DB, Schema, Crypt, Queue, Redis};
use App\Jobs\GravarAuditoria;
use App\Services\{ConferenciaAuditoria, ArquivoMensalAuditoria};
if (DB::connection()->getDatabaseName()!=='gabarito-360' || config('database.connections.'.config('database.default').'.host')!=='10.0.0.6') exit(2);
function exigir($ok,$codigo) {if (!$ok) throw new RuntimeException($codigo);}
function guardar($nome,$valor) {file_put_contents('/root/au1-'.$nome.'.json',json_encode($valor,JSON_THROW_ON_ERROR));chmod('/root/au1-'.$nome.'.json',0600);}
function evento($n) {return ['id'=>sprintf('a1000000-0000-4000-8000-%012d',$n),'mes'=>'2026-10','ocorrido_em'=>'2026-10-31 12:00:00.123456','metodo'=>'GET','rota'=>'api/v1/alunos/{id}','referencias'=>'{"id":"1"}','status_http'=>200,'resultado'=>'sucesso'];}
function inventario() {
    $tabelas=[];
    foreach (Schema::getTables(DB::connection()->getDatabaseName()) as $t) {
        $nome=$t['name']; if ($nome==='auditoria_eventos') continue;
        $idx=collect(Schema::getIndexes($nome))->firstWhere('primary',true);
        $q=DB::table($nome); foreach ($idx['columns']??Schema::getColumnListing($nome) as $c) $q->orderBy($c);
        $hash=hash_init('sha256'); $n=0;
        foreach ($q->cursor() as $row) {hash_update($hash,json_encode($row,JSON_THROW_ON_ERROR)."\n");$n++;}
        $tabelas[$nome]=['quantidade'=>$n,'sha256'=>hash_final($hash)];
    }
    $arquivos=[]; $public=realpath(storage_path('app/public'));
    foreach (DB::table('cartoes')->whereNotNull('imagem_url')->pluck('imagem_url') as $v) {
        $p=realpath($public.'/'.$v);exigir($p && str_starts_with($p,$public.'/') && is_readable($p),'arquivo_ausente');
        $arquivos[$v]=hash_file('sha256',$p);
    }
    ksort($arquivos);ksort($tabelas);
    $cifras=0;
    foreach (['usuarios'=>['email','cpf','telefone'],'alunos'=>['cpf'],'escolas'=>['email','telefone'],'leads'=>['email','telefone'],'trocas_email'=>['novo_email']] as $t=>$cols) {
        foreach (DB::table($t)->cursor() as $row) foreach ($cols as $col) {
            $v=$row->$col;if ($v===null) continue;
            exigir(str_starts_with($v,'g360:v1:'),'cifra_invalida');
            $claro=Crypt::decryptString(substr($v,8));
            if (property_exists($row,$col.'_hash')) exigir(App\Support\DadoProtegido::hash($claro,$col)===$row->{$col.'_hash'},'indice_invalido');
            unset($claro);$cifras++;
        }
    }
    return ['tabelas'=>$tabelas,'arquivos'=>$arquivos,'cifras'=>$cifras];
}
try {
    $fase=$argv[1]??'';$inicio=microtime(true);$r=['fase'=>$fase];
    if ($fase==='inventario') {
        $i=inventario();guardar('inventario-base',$i);
        $r+=['tabelas'=>count($i['tabelas']),'arquivos'=>count($i['arquivos']),'cifras'=>$i['cifras']];
    } elseif ($fase==='popular') {
        exigir(gethostname()==='g360-au1-app-origem' && DB::table('auditoria_eventos')->count()===0,'base_nao_vazia');
        $inicioDia=new DateTimeImmutable('2026-05-05',new DateTimeZone('UTC'));
        for ($dia=0;$dia<180;$dia++) {
            $data=$inicioDia->modify('+'.$dia.' days')->format('Y-m-d');
            for ($lote=0;$lote<10;$lote++) {
                $linhas=[];
                for ($k=1;$k<=1000;$k++) {
                    $n=$dia*10000+$lote*1000+$k;$e=evento($n);$e['mes']=substr($data,0,7);$e['ocorrido_em']=$data.' 12:00:00.123456';
                    $e['usuario_real_id']=($n%100)+1;$e['espaco_id']=($n%13)+1;$e['perfil']='professor';
                    $e['metodo']=$n%4 ? 'GET':'POST';$e['status_http']=$n%10 ? 200:403;$e['resultado']=$n%10 ? 'sucesso':'recusado';
                    $linhas[]=$e;
                }
                DB::table('auditoria_eventos')->insert($linhas);
            }
        }
        $r['eventos']=DB::table('auditoria_eventos')->count();exigir($r['eventos']===1800000,'carga_incompleta');
    } elseif ($fase==='medir') {
        $s=app(ConferenciaAuditoria::class);$t=microtime(true);$m=$s->executar();guardar('manifesto',$m);
        $r['inventario_s']=round(microtime(true)-$t,3);$r['eventos']=array_sum(array_column($m['meses'],'quantidade'));
        exigir($r['eventos']===1800000 && $m['meses']['2026-05']['preservado'],'manifesto_invalido');
        $t=microtime(true);$arq=app(ArquivoMensalAuditoria::class)->criar('2026-09','/root/au1-mensal');
        $r['arquivo_s']=round(microtime(true)-$t,3);$r['arquivo_bytes']=filesize('/root/au1-mensal/eventos.enc');
        exigir($arq['sha256']===$m['meses']['2026-09']['sha256'],'arquivo_diverge');
        $t=microtime(true);app(ArquivoMensalAuditoria::class)->verificar('2026-09','/root/au1-mensal');$r['verificar_arquivo_s']=round(microtime(true)-$t,3);
        $tempos=[];
        for ($i=0;$i<50;$i++) {$t=microtime(true);exigir(DB::table('auditoria_eventos')->where('mes','2026-09')->orderBy('ocorrido_em')->limit(100)->get()->count()===100,'consulta_incompleta');$tempos[]=(microtime(true)-$t)*1000;}
        sort($tempos);$r['consulta_100_p95_ms']=round($tempos[47],3);
        $r['memoria_php_pico_bytes']=memory_get_peak_usage(true);guardar('medidas',$r);
    } elseif ($fase==='enfileirar') {
        exigir(Queue::connection('redis')->size('baixa')===0,'fila_nao_vazia');
        for ($n=1800001;$n<=1801000;$n++) app(Illuminate\Contracts\Bus\Dispatcher::class)->dispatch((new GravarAuditoria(evento($n)))->onConnection('redis'));
        $r['pendentes']=Queue::connection('redis')->size('baixa');exigir($r['pendentes']===1000,'fila_incompleta');
    } elseif ($fase==='checkpoint') {
        exigir(DB::table('auditoria_eventos')->count()===1801000 && Queue::connection('redis')->size('baixa')===0,'worker_incompleto');
        $i=inventario();guardar('inventario-checkpoint',$i);
        guardar('manifesto-checkpoint',app(ConferenciaAuditoria::class)->executar());
        app(Illuminate\Contracts\Bus\Dispatcher::class)->dispatch((new GravarAuditoria(evento(1801001)))->onConnection('redis'));
        Redis::connection()->command('save');$r['pendentes']=Queue::connection('redis')->size('baixa');exigir($r['pendentes']===1,'pendencia_ausente');
    } elseif ($fase==='conferir') {
        $i=inventario();exigir($i===json_decode(file_get_contents('/root/au1-inventario-checkpoint.json'),true),'dados_ou_arquivos_divergem');
        $s=app(ConferenciaAuditoria::class);exigir($s->comparar($s->executar(),json_decode(file_get_contents('/root/au1-manifesto-checkpoint.json'),true)),'auditoria_diverge');
        app(ArquivoMensalAuditoria::class)->verificar('2026-09','/root/au1-mensal');
        exigir(Queue::connection('redis')->size('baixa')===1,'pendencia_perdida');
        $negados=0;foreach (['DELETE FROM auditoria_eventos WHERE id=\'inexistente\'','UPDATE auditoria_eventos SET resultado=\'erro\' WHERE id=\'inexistente\'','ALTER TABLE auditoria_eventos ADD COLUMN indevida INT'] as $sql) {
            try {DB::connection('auditoria_gravacao')->statement($sql);} catch (Illuminate\Database\QueryException $e) {if (($e->errorInfo[1]??0)===1142)$negados++;}
        }
        exigir($negados===3,'grants_excessivos');$r+=['manifesto_confere'=>true,'arquivo_confere'=>true,'grants_restritos'=>true,'cifras'=>$i['cifras'],'arquivos'=>count($i['arquivos'])];
    } elseif ($fase==='replay') {
        exigir(DB::table('auditoria_eventos')->count()===1801001 && Queue::connection('redis')->size('baixa')===0,'consumo_incompleto');
        app(Illuminate\Contracts\Bus\Dispatcher::class)->dispatch((new GravarAuditoria(evento(1801001)))->onConnection('redis'));
        $r['replay_enfileirado']=true;
    } elseif (in_array($fase,['final','http'],true)) {
        $esperado=$fase==='http' ? 1801002 : 1801001;
        exigir(DB::table('auditoria_eventos')->count()===$esperado && Queue::connection('redis')->size('baixa')===0,'replay_diverge');
        exigir(DB::table('failed_jobs')->count()===0,'job_falho');
        if ($fase==='http') exigir(DB::table('auditoria_eventos')->where('rota','api/v1/alunos')->where('status_http',401)->count()===1,'http_nao_auditado');
        $r+=['eventos'=>$esperado,'fila_vazia'=>true,'sem_duplicacao'=>true,'falhos'=>0];
    } else throw new RuntimeException('fase_desconhecida');
    $r['duracao_s']=round(microtime(true)-$inicio,3);echo json_encode($r,JSON_THROW_ON_ERROR).PHP_EOL;
} catch(Throwable $e) {
    $codigo=$e instanceof Illuminate\Database\QueryException ? ' SQLSTATE='.$e->getCode().' driver='.($e->errorInfo[1]??0) : '';
    if ($fase==='inventario' && $e instanceof Illuminate\Database\QueryException) guardar('erro-inventario',['sql'=>$e->getSql(),'codigo'=>$e->errorInfo[1]??0]);
    fwrite(STDERR,'Ensaio interrompido: '.get_class($e).$codigo."; diagnóstico local restrito.\n");exit(1);
}
