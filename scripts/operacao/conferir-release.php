<?php
// Diagnóstico somente leitura da origem; saída sem credenciais, nomes ou conteúdo escolar.
if (count($argv)!==3) exit(2);
[$script,$raiz,$candidato]=$argv;
try {
    require $raiz.'/apps/api/vendor/autoload.php';
    $app=require $raiz.'/apps/api/bootstrap/app.php';
    $app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
    $aplicadas=Illuminate\Support\Facades\DB::table('migrations')->pluck('migration')->all();
    $arquivos=glob($candidato.'/apps/api/database/migrations/*.php');
    $pendentes=array_values(array_diff(array_map(fn($p)=>basename($p,'.php'),$arquivos),$aplicadas));
    sort($pendentes);
    $flags=[];
    foreach(['espacos.ativo','vinculos.obrigatorio','acessos_internos.ativo','auditoria.ativa','copias.ativo','copias.interface_ativa','copias.execucao_ativa'] as $k) $flags[$k]=(bool)config($k,false);
    $filas=[]; foreach(['alta','padrao','baixa'] as $f) $filas[$f]=Illuminate\Support\Facades\Queue::connection('redis')->size($f);
    $vinculos=['inventario_disponivel'=>false,'requisito'=>'migracao_v1_pendente',
        'usuarios'=>Illuminate\Support\Facades\DB::table('usuarios')->count(),
        'por_perfil'=>Illuminate\Support\Facades\DB::table('usuarios')->selectRaw('perfil, COUNT(*) as total')->groupBy('perfil')->get()->all(),
        'ajustes_individuais'=>Illuminate\Support\Facades\DB::table('usuario_permissoes')->count()];
    if(Illuminate\Support\Facades\Schema::hasTable('usuario_vinculos')) {
        // O inventário detalhado fica privado, com IDs operacionais.
        require_once $candidato.'/apps/api/app/Services/InventariarVinculosLegados.php';
        $inventario=(new App\Services\InventariarVinculosLegados)->executar();
        $arquivo=dirname($candidato).'/vinculos-inventario-privado.json';
        file_put_contents($arquivo,json_encode($inventario,JSON_PRETTY_PRINT|JSON_THROW_ON_ERROR)); chmod($arquivo,0600);
        $motivos=[]; foreach($inventario['pessoas'] as $p) foreach($p['pendencias'] as $m) $motivos[$m]=($motivos[$m]??0)+1;
        $vinculos=['inventario_disponivel'=>true,'usuarios'=>$inventario['usuarios'],'candidatos'=>$inventario['candidatos'],'pendencias_por_motivo'=>$motivos];
    }
    echo json_encode(['somente_leitura'=>true,'migracoes_pendentes'=>$pendentes,'flags_publicadas'=>$flags,'filas'=>$filas,
        'jobs_falhos'=>Illuminate\Support\Facades\DB::table('failed_jobs')->count(),
        'vinculos'=>$vinculos,
        'publicacao_executada'=>false],JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE|JSON_THROW_ON_ERROR).PHP_EOL;
} catch(Throwable $e) {
    $erro=dirname($candidato).'/erro-conferencia-privado.txt';
    file_put_contents($erro,get_class($e)."\n".$e->getMessage()); chmod($erro,0600);
    fwrite(STDERR,"Conferência falhou: ".get_class($e)." (código ".$e->getCode()."). Detalhes em arquivo privado; nenhuma migração executada.\n"); exit(1);
}
