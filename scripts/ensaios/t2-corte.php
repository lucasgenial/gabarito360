<?php
/** Ensaio inteiramente fictício. Uso e limites em docs/49. Não carregar .env de produção. */
use App\Services\AuditarIntegridadeEspacos;
use App\Services\PreencherEspacosLegados;
use App\Services\RestringirEspacos;
use App\Support\EstruturaEspacos;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;

$opcoes = getopt('', ['recriar-banco', 'escala:']);
$escala = filter_var($opcoes['escala'] ?? 1, FILTER_VALIDATE_INT);
if (!array_key_exists('recriar-banco', $opcoes) || !in_array($escala, [1, 10], true)) {
    fwrite(STDERR, "Uso isolado: php scripts/ensaios/t2-corte.php --recriar-banco --escala=1|10\n"); exit(2);
}
$raiz = dirname(__DIR__, 2).'/apps/api';
require $raiz.'/vendor/autoload.php';
$app = require $raiz.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
$tempos = [];
$medir = function (string $etapa, callable $acao) use (&$tempos) {
    $inicio = hrtime(true); $r = $acao(); $tempos[$etapa] = round((hrtime(true) - $inicio) / 1e6, 2); return $r;
};
try {
    $conn = DB::connection();
    if (!$app->environment('testing') || !in_array($conn->getDriverName(), ['mysql', 'mariadb'], true)
        || $conn->getDatabaseName() !== 'g360_t2_ensaio_test'
        || $conn->getConfig('unix_socket') !== '/tmp/g360-t2-mariadb/mysql.sock') throw new RuntimeException('Ambiente não permitido.');
    $servidor = DB::selectOne('SELECT @@socket as socket, @@datadir as pasta, @@skip_networking as isolado, VERSION() as versao');
    if ($servidor->socket !== '/tmp/g360-t2-mariadb/mysql.sock' || rtrim($servidor->pasta, '/') !== '/tmp/g360-t2-mariadb/data'
        || !(int) $servidor->isolado || !str_contains($servidor->versao, 'MariaDB')) throw new RuntimeException('Servidor não isolado.');
    config(['mail.default' => 'array', 'queue.default' => 'sync', 'cache.default' => 'array', 'espacos.ativo' => false]);
    $expansao = '2026_10_31_000006_expandir_clientes_e_espacos.php';
    $paths = array_values(array_map(fn ($p) => 'database/migrations/'.basename($p), array_filter(glob($raiz.'/database/migrations/*.php'), fn ($p) => basename($p) !== $expansao)));
    $medir('recriar_esquema_legado', function () use ($paths) {
        if (Artisan::call('migrate:fresh', ['--force' => true, '--path' => $paths]) !== 0) throw new RuntimeException('Migração fictícia falhou.');
    });
    $medir('carga_ficticia', function () use ($escala) {
        DB::transaction(function () use ($escala) {
            foreach (EstruturaEspacos::MANIFESTO as $rede => $nucleos) {
                DB::table('redes')->insert(['id' => $rede, 'nome' => 'Rede fictícia', 'tipo' => 'estadual', 'uf' => 'BA']);
                foreach ($nucleos as $nucleo => $escolas) {
                    DB::table('nucleos')->insert(['id' => $nucleo, 'rede_id' => $rede, 'nome' => 'Núcleo fictício']);
                    foreach ($escolas as $escola) DB::table('escolas')->insert(['id' => $escola, 'nucleo_id' => $nucleo, 'nome' => 'Escola fictícia', 'inep' => str_pad((string) $escola, 8, '0', STR_PAD_LEFT), 'tipo_rede' => 'estadual']);
                }
            }
            DB::table('usuarios')->insert(['id' => 1, 'nome' => 'Operador fictício', 'perfil' => 'admin_rede', 'email' => 'ensaio@example.test', 'cpf' => '00000000001', 'password' => 'sem-login']);
            $idAluno = 0;
            for ($t = 1; $t <= 14; $t++) {
                DB::table('turmas')->insert(['id' => $t, 'escola_id' => $t <= 12 ? 1 : 8, 'nome' => 'Fictícia '.$t, 'serie' => '3', 'turno' => 'tarde', 'ano_letivo' => 2026]);
                $rows = [];
                for ($a = 0; $a < ($t <= 12 ? 30 : 27) * $escala; $a++) {
                    $idAluno++; $rows[] = ['id' => $idAluno, 'turma_id' => $t, 'nome' => 'Aluno fictício', 'matricula' => 'TESTE-'.$idAluno];
                }
                foreach (array_chunk($rows, 200) as $lote) DB::table('alunos')->insert($lote);
            }
            for ($p = 1; $p <= 4; $p++) {
                DB::table('provas')->insert(['id' => $p, 'escola_id' => 8, 'criado_por' => 1, 'titulo' => 'Prova fictícia', 'disciplina' => 'Teste', 'serie' => '3', 'num_questoes' => 45, 'num_alternativas' => 5]);
                foreach ([13, 14] as $t) DB::table('prova_turmas')->insert(['prova_id' => $p, 'turma_id' => $t]);
                DB::table('gabaritos')->insert(['id' => $p, 'prova_id' => $p]);
                $rows = [];
                for ($q = 1; $q <= 45; $q++) $rows[] = ['gabarito_id' => $p, 'numero_questao' => $q, 'alternativa' => 'A'];
                DB::table('gabarito_questoes')->insert($rows);
            }
            for ($c = 1; $c <= 47 * $escala; $c++) {
                $aluno = 360 * $escala + $c; $turma = $c <= 27 * $escala ? 13 : 14;
                DB::table('cartoes')->insert(['id' => $c, 'prova_id' => 1, 'aluno_id' => $aluno, 'status' => 'lido']);
                $rows = [];
                for ($q = 1; $q <= 45; $q++) $rows[] = ['id' => ($c - 1) * 45 + $q, 'cartao_id' => $c, 'numero_questao' => $q, 'alternativa' => 'A'];
                DB::table('cartao_respostas')->insert($rows);
                DB::table('notas')->insert(['cartao_id' => $c, 'prova_id' => 1, 'aluno_id' => $aluno, 'turma_id' => $turma, 'acertos' => 45, 'total_questoes' => 45, 'nota_final' => 10, 'status_aprovacao' => 'aprovado']);
            }
            for ($l = 1; $l <= 28 * $escala; $l++) DB::table('ambiguidade_logs')->insert(['cartao_id' => (int) ceil($l / 45), 'cartao_resposta_id' => $l, 'usuario_id' => 1, 'alternativa_escolhida' => 'A']);
        });
    });
    $medir('expansao', function () {
        if (Artisan::call('migrate', ['--force' => true]) !== 0) throw new RuntimeException('Expansão falhou.');
    });
    $antes = $medir('inventario', fn () => app(PreencherEspacosLegados::class)->executar());
    $medir('preenchimento', fn () => app(PreencherEspacosLegados::class)->executar(true));
    $medir('conciliacao', function () {
        if (Artisan::call('g360:conciliar-admin-espacos', ['--usuario' => 1, '--manifesto' => EstruturaEspacos::VERSAO, '--aplicar' => true]) !== 0) throw new RuntimeException('Conciliação falhou.');
    });
    $auditoria = $medir('auditoria_antes', fn () => app(AuditarIntegridadeEspacos::class)->executar());
    if (!$auditoria['apto_dados']) throw new RuntimeException('Auditoria recusou ensaio.');
    // Apenas nesta instância CLI de testing; não escreve arquivo nem altera a configuração versionada.
    config(['espacos.ativo' => true]);
    $ddl = $medir('restricao', fn () => app(RestringirEspacos::class)->executar(true));
    $repeticao = $medir('restricao_repetida', fn () => app(RestringirEspacos::class)->executar(true));
    $depois = $medir('auditoria_depois', fn () => app(AuditarIntegridadeEspacos::class)->executar());
    if (!$depois['apto_dados'] || $antes['contagens'] != array_diff_key($depois['contagens'], ['sincronizacoes_seges' => true])
        || $repeticao['passos_pendentes_antes']) throw new RuntimeException('Comparação ou repetição divergente.');
    echo json_encode(['ficticio' => true, 'referencia' => 'docs/42, fotografia histórica de 31/10/2026', 'escala_alunos_e_correcoes' => $escala,
        'mariadb' => $servidor->versao, 'tempos_ms' => $tempos, 'ddl_passos_ms' => $ddl['tempos_ms'], 'contagens' => $depois['contagens'],
        'ocorrencias' => $depois['ocorrencias'], 'repeticao_sem_ddl' => true, 'producao_alterada' => false], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR).PHP_EOL;
} catch (Throwable $e) {
    fwrite(STDERR, 'Ensaio interrompido; diagnóstico técnico: '.get_class($e).". Nenhum ambiente de produção é permitido.\n"); exit(1);
}
