<?php
/** Validação restrita de cópia autorizada. Nunca exibe configuração, SQL ou conteúdo escolar. */
if (!in_array('--aplicar-na-copia', $argv, true)) { fwrite(STDERR, "Exige --aplicar-na-copia em ambiente isolado documentado.\n"); exit(2); }
$root = '/restauracao-t2';
$base = $root.'/candidato/apps/api';
try {
    // Mesmo que alguém use o banco correto, o cliente PHP também deve estar sem interfaces externas.
    $interfaces = file_get_contents('/proc/net/dev');
    if (preg_match('/^\s*(?!lo:)[a-zA-Z0-9_-]+:/m', $interfaces)) throw new RuntimeException('Rede não isolada.');
    require $base.'/vendor/autoload.php';
    $segredos = Dotenv\Dotenv::parse(file_get_contents($root.'/atual/apps/api/.env'));
    foreach (['APP_KEY', 'DADOS_HASH_KEY'] as $k) if (empty($segredos[$k])) throw new RuntimeException('Chave ausente.');
    $env = ['APP_ENV' => 'testing', 'APP_DEBUG' => 'false', 'APP_URL' => 'http://127.0.0.1', 'DB_URL' => '',
        'DB_CONNECTION' => 'mariadb', 'DB_DATABASE' => 'g360_t2_validacao', 'DB_USERNAME' => 'root', 'DB_PASSWORD' => '',
        'DB_SOCKET' => '/tmp/g360-t2-mariadb/mysql.sock', 'CACHE_STORE' => 'array', 'SESSION_DRIVER' => 'array',
        'QUEUE_CONNECTION' => 'sync', 'MAIL_MAILER' => 'array'];
    foreach (['APP_KEY', 'DADOS_HASH_KEY', 'APP_PREVIOUS_KEYS'] as $k) if (isset($segredos[$k])) $env[$k] = $segredos[$k];
    unset($segredos);
    foreach ($env as $k => $v) { $_ENV[$k] = $_SERVER[$k] = $v; putenv($k.'='.$v); }
    unset($env);
    $app = require $base.'/bootstrap/app.php';
    $app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
    $db = Illuminate\Support\Facades\DB::connection();
    $s = $db->selectOne('SELECT @@socket as socket, @@datadir as pasta, @@skip_networking as isolado, VERSION() as versao');
    if (!$app->environment('testing') || $db->getDatabaseName() !== 'g360_t2_validacao' || !$s->isolado
        || $s->socket !== '/tmp/g360-t2-mariadb/mysql.sock' || rtrim($s->pasta, '/') !== '/tmp/g360-t2-mariadb/data') throw new RuntimeException('Destino recusado.');
    if (!config('espacos.ativo')) throw new RuntimeException('Candidato sem ativação.');
    $tempos = []; $medir = function ($nome, $fn) use (&$tempos) { $t = hrtime(true); $r = $fn(); $tempos[$nome] = round((hrtime(true)-$t)/1e6,2); return $r; };
    $campos = []; $antes = []; $contagensAntes = [];
    foreach (App\Support\EstruturaEspacos::TABELAS as $t) {
        $campos[$t] = Illuminate\Support\Facades\Schema::getColumnListing($t);
        $rows = $db->table($t)->select($campos[$t])->orderBy($t === 'prova_turmas' ? 'prova_id' : 'id');
        if ($t === 'prova_turmas') $rows->orderBy('turma_id');
        $rows = $rows->get(); $contagensAntes[$t] = $rows->count(); $antes[$t] = hash('sha256', $rows->toJson());
    }
    $cifras = 0; $amostra = null;
    foreach (['usuarios' => ['email','cpf','telefone'], 'alunos' => ['cpf'], 'escolas' => ['email','telefone'], 'leads' => ['email','telefone'], 'trocas_email' => ['novo_email']] as $t => $cols) {
        foreach ($db->table($t)->get() as $row) foreach ($cols as $col) {
            $valor = $row->$col; if ($valor === null) continue;
            if (!str_starts_with($valor, 'g360:v1:')) throw new RuntimeException('Cifra ausente.');
            $claro = Illuminate\Support\Facades\Crypt::decryptString(substr($valor,8));
            if (property_exists($row,$col.'_hash') && App\Support\DadoProtegido::hash($claro,$col) !== $row->{$col.'_hash'}) throw new RuntimeException('Índice protegido divergente.');
            $amostra ??= substr($valor,8); $cifras++; unset($claro);
        }
    }
    $chaveErradaRecusada = false;
    try { (new Illuminate\Encryption\Encrypter(str_repeat("\0",32),'AES-256-CBC'))->decryptString($amostra); }
    catch (Illuminate\Contracts\Encryption\DecryptException $e) { $chaveErradaRecusada = true; }
    if (!$chaveErradaRecusada) throw new RuntimeException('Teste negativo da cifra falhou.');
    $arquivos = 0; $public = realpath($root.'/atual/apps/api/storage/app/public');
    foreach ($db->table('cartoes')->whereNotNull('imagem_url')->pluck('imagem_url') as $path) {
        $p = realpath($public.'/'.$path);
        if (!$p || !str_starts_with($p, $public.'/') || !is_readable($p)) throw new RuntimeException('Arquivo referenciado ausente.');
        $arquivos++;
    }
    $medir('expansao', function () { if (Illuminate\Support\Facades\Artisan::call('migrate',['--force'=>true]) !== 0) throw new RuntimeException('Expansão falhou.'); });
    $servico = app(App\Services\RestringirEspacos::class);
    if ($servico->esquemaConcluido()) throw new RuntimeException('Pré-condição de esquema inesperada.');
    $medir('preenchimento', fn () => app(App\Services\PreencherEspacosLegados::class)->executar(true));
    $admins = App\Models\Usuario::where('perfil','admin_rede')->where('ativo',true)->get();
    if ($admins->count() !== 1) throw new RuntimeException('Administrador ambíguo.');
    $medir('conciliacao', function () use ($admins) {
        if (Illuminate\Support\Facades\Artisan::call('g360:conciliar-admin-espacos',['--usuario'=>$admins[0]->id,'--manifesto'=>App\Support\EstruturaEspacos::VERSAO,'--aplicar'=>true]) !== 0) throw new RuntimeException('Conciliação falhou.');
    });
    $auditoria = app(App\Services\AuditarIntegridadeEspacos::class)->executar();
    if (!$auditoria['apto_dados']) throw new RuntimeException('Auditoria recusada.');
    $medir('restricao', fn () => $servico->executar(true));
    if (!$servico->esquemaConcluido()) throw new RuntimeException('Esquema incompleto.');
    $repeticao = $medir('repeticao', fn () => $servico->executar());
    if ($repeticao['passos_pendentes_antes']) throw new RuntimeException('Passos restantes.');
    foreach ($campos as $t => $cols) {
        $q = $db->table($t)->select($cols)->orderBy($t === 'prova_turmas' ? 'prova_id' : 'id');
        if ($t === 'prova_turmas') $q->orderBy('turma_id');
        if (hash('sha256',$q->get()->toJson()) !== $antes[$t]) throw new RuntimeException('Conteúdo anterior divergiu.');
    }
    $db->statement('SET SESSION max_statement_time = 15');
    $http = []; $httpTempos = []; $kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
    $requisitar = function ($metodo,$uri,$dados=[], $token=null) use ($kernel,&$httpTempos) {
        Illuminate\Support\Facades\Auth::forgetGuards(); App\Support\Permissao::limparCache();
        $headers = ['CONTENT_TYPE'=>'application/json','HTTP_ACCEPT'=>'application/json'];
        if ($token) $headers['HTTP_AUTHORIZATION']='Bearer '.$token;
        $req=Illuminate\Http\Request::create($uri,$metodo,[],[],[],$headers,json_encode($dados));
        $t=hrtime(true); $resp=$kernel->handle($req); $httpTempos[$metodo.' '.$uri]=round((hrtime(true)-$t)/1e6,2); $kernel->terminate($req,$resp); return $resp;
    };
    $db->beginTransaction();
    try {
        $u=App\Models\Usuario::create(['nome'=>'Ensaio temporário','perfil'=>'admin_rede','email'=>'ensaio-t2-ativacao@example.test','cpf'=>'99999999998','password'=>bcrypt('senha-ficticia-ensaio'),'ativo'=>true]);
        $db->table('usuario_escopos')->insert(['usuario_id'=>$u->id,'escopo_tipo'=>'rede','escopo_id'=>1]);
        $login=$requisitar('POST','/api/v1/auth/login',['email'=>'ensaio-t2-ativacao@example.test','password'=>'senha-ficticia-ensaio']);
        $token=json_decode($login->getContent(),true)['data']['token'] ?? null;
        $http['login']=$login->getStatusCode(); if (!$token) throw new RuntimeException('Login recusado.');
        $http['painel']=$requisitar('GET','/api/v1/dashboard/admin',[],$token)->getStatusCode();
        $http['rede_alheia']=$requisitar('GET','/api/v1/relatorios/rede/2',[],$token)->getStatusCode();
        $db->table('usuario_escopos')->insert(['usuario_id'=>$u->id,'escopo_tipo'=>'rede','escopo_id'=>2]);
        $http['segunda_rede_autorizada']=$requisitar('GET','/api/v1/relatorios/rede/2',[],$token)->getStatusCode();
        if ($http !== ['login'=>200,'painel'=>200,'rede_alheia'=>404,'segunda_rede_autorizada'=>200]) throw new RuntimeException('Fluxo de acesso divergiu.');
    } finally { $db->rollBack(); }
    $final=app(App\Services\AuditarIntegridadeEspacos::class)->executar();
    if (!$final['apto_dados'] || $final['contagens'] !== $contagensAntes) throw new RuntimeException('Auditoria final divergiu.');
    echo json_encode(['copia_isolada'=>true,'mariadb'=>$s->versao,'adaptador_candidato_ativo'=>true,'esquema_completo'=>true,
        'contagens'=>$final['contagens'],'conteudo_anterior_preservado'=>true,'cifras_validadas'=>$cifras,'chave_errada_recusada'=>$chaveErradaRecusada,
        'arquivos_cartoes_recuperados'=>$arquivos,'http_kernel'=>$http,'http_tempos_ms'=>$httpTempos,'tempos_ms'=>$tempos,'ocorrencias'=>$final['ocorrencias'],
        'sem_escrita_producao'=>true],JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE|JSON_THROW_ON_ERROR).PHP_EOL;
} catch (Throwable $e) {
    fwrite(STDERR,'Validação interrompida: '.get_class($e)."; investigar somente no ambiente restrito.\n"); exit(1);
}
