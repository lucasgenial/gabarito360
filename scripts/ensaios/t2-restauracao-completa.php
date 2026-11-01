<?php
/** Exclusivo do CT temporário 9111, sem ligação com rede externa. Nunca executar no servidor atual. */
$base='/home/lucasdev/projetos/gabarito360/apps/api';
if (gethostname() !== 'g360-t2-app-20261101' || !is_file('/etc/g360-t2-ensaio')) exit(2);
$rotas=json_decode(shell_exec('ip -j route'),true);
if (!is_array($rotas) || array_filter($rotas,fn($r)=>($r['dst']??'default')!=='10.0.0.0/24')) exit(2);
require $base.'/vendor/autoload.php';
$app=require $base.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Crypt;
if (config('database.connections.mysql.host') !== '10.0.0.6' || DB::connection()->getDatabaseName() !== 'gabarito-360') exit(2);
$modo=$argv[1]??'';
try {
    if ($modo==='inventario') {
        if (!config('app.key') || strlen((string)config('dados.hash_key'))<32) throw new RuntimeException();
        $tabelas=['turmas','alunos','provas','prova_turmas','gabaritos','gabarito_questoes','cartoes','cartao_respostas','notas','ambiguidade_logs','cartao_envios','visitas','sincronizacoes_seges'];
        $antes=[];
        foreach($tabelas as $t) {
            $cols=Schema::getColumnListing($t);
            $q=DB::table($t)->select($cols)->orderBy($t==='prova_turmas'?'prova_id':'id');
            if($t==='prova_turmas')$q->orderBy('turma_id');
            $rows=$q->get();$antes[$t]=['colunas'=>$cols,'contagem'=>$rows->count(),'hash'=>hash('sha256',$rows->toJson())];
        }
        $cifras=0;
        foreach(['usuarios'=>['email','cpf','telefone'],'alunos'=>['cpf'],'escolas'=>['email','telefone'],'leads'=>['email','telefone'],'trocas_email'=>['novo_email']] as $t=>$cols) {
            foreach(DB::table($t)->get() as $row)foreach($cols as $col) {
                $v=$row->$col;if($v===null)continue;
                if(!str_starts_with($v,'g360:v1:'))throw new RuntimeException();
                $claro=Crypt::decryptString(substr($v,8));
                if(property_exists($row,$col.'_hash') && App\Support\DadoProtegido::hash($claro,$col)!==$row->{$col.'_hash'})throw new RuntimeException();
                $cifras++;unset($claro);
            }
        }
        $arquivos=0;$public=realpath($base.'/storage/app/public');
        foreach(DB::table('cartoes')->whereNotNull('imagem_url')->pluck('imagem_url') as $v) {
            $p=realpath($public.'/'.$v);
            if(!$p || !str_starts_with($p,$public.'/') || !is_readable($p))throw new RuntimeException();$arquivos++;
        }
        file_put_contents('/root/t2-inventario.json',json_encode($antes,JSON_THROW_ON_ERROR));chmod('/root/t2-inventario.json',0600);
        echo json_encode(['chaves_recuperadas'=>true,'cifras'=>$cifras,'arquivos'=>$arquivos,'contagens'=>array_map(fn($v)=>$v['contagem'],$antes)],JSON_THROW_ON_ERROR).PHP_EOL;
    } elseif($modo==='pos-corte') {
        $antes=json_decode(file_get_contents('/root/t2-inventario.json'),true,512,JSON_THROW_ON_ERROR);
        foreach($antes as $t=>$v) {
            $q=DB::table($t)->select($v['colunas'])->orderBy($t==='prova_turmas'?'prova_id':'id');
            if($t==='prova_turmas')$q->orderBy('turma_id');
            $rows=$q->get();if($rows->count()!==$v['contagem'] || hash('sha256',$rows->toJson())!==$v['hash'])throw new RuntimeException();
        }
        $audit=app(App\Services\AuditarIntegridadeEspacos::class)->executar();
        if(!$audit['apto_dados'] || !app(App\Services\RestringirEspacos::class)->esquemaConcluido())throw new RuntimeException();
        echo json_encode(['conteudo_preservado'=>true,'ocorrencias'=>$audit['ocorrencias'],'esquema_completo'=>true],JSON_THROW_ON_ERROR).PHP_EOL;
    } elseif($modo==='conta') {
        $u=App\Models\Usuario::create(['nome'=>'Ensaio técnico','email'=>'restauracao-t2@example.test','cpf'=>'99999999998','password'=>bcrypt('senha-ficticia-restauracao'),'perfil'=>'admin_rede','ativo'=>true,'forcar_troca_senha'=>false]);
        DB::table('usuario_escopos')->insert(['usuario_id'=>$u->id,'escopo_tipo'=>'rede','escopo_id'=>1]);
        file_put_contents('/root/t2-usuario-ficticio', (string)$u->id);
        echo "conta_ficticia_criada\n";
    } elseif($modo==='segunda-rede') {
        $id=(int)file_get_contents('/root/t2-usuario-ficticio');
        DB::table('usuario_escopos')->insert(['usuario_id'=>$id,'escopo_tipo'=>'rede','escopo_id'=>2]);echo "vinculo_ficticio_criado\n";
    } elseif($modo==='fila') {
        App\Jobs\EnsaioRecuperacaoJob::dispatch()->onQueue('padrao');echo "job_ficticio_enfileirado\n";
    } elseif($modo==='estado-fila') {
        echo json_encode(['falhos'=>DB::table('failed_jobs')->count(),'pendentes'=>array_sum(array_map(fn($q)=>Illuminate\Support\Facades\Queue::size($q),['alta','padrao','baixa']))],JSON_THROW_ON_ERROR).PHP_EOL;
    } elseif($modo==='admin') {
        $ids=DB::table('usuarios')->where('perfil','admin_rede')->where('ativo',1)->pluck('id');
        if($ids->count()!==1)throw new RuntimeException();echo $ids[0];
    } else exit(2);
} catch(Throwable $e) {fwrite(STDERR,'Ensaio interrompido: '.get_class($e)."; diagnóstico restrito ao clone.\n");exit(1);}
