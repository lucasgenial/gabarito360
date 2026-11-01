<?php

namespace Tests\Feature;

use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/** Executa DDL somente em MariaDB descartável, nunca no banco da aplicação. */
class VinculosMariaDbTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        if (!in_array(DB::connection()->getDriverName(), ['mysql', 'mariadb'], true)) $this->markTestSkipped('Exige MariaDB descartável.');
        if (!str_ends_with(DB::connection()->getDatabaseName(), '_test')) throw new \RuntimeException('Use banco descartável terminado em _test.');
        $this->artisan('migrate:fresh', ['--force' => true])->assertSuccessful();
        DB::table('usuarios')->insert(['id' => 1, 'perfil' => 'admin_rede', 'nome' => 'Fictício', 'email' => 'v1@example.test', 'cpf' => '00000000001', 'password' => 'ficticio']);
        DB::table('redes')->insert(['id' => 1, 'nome' => 'Rede', 'tipo' => 'estadual', 'uf' => 'BA']);
        DB::table('clientes')->insert(['id' => 1, 'nome' => 'Cliente', 'tipo' => 'rede', 'modalidade' => 'interno', 'situacao' => 'ativo']);
        DB::table('espacos')->insert(['id' => 1, 'cliente_id' => 1, 'tipo' => 'rede', 'nome' => 'Rede', 'situacao' => 'ativo', 'rede_id' => 1]);
    }

    private function recusa(callable $acao): void
    {
        try { $acao(); $this->fail('MariaDB deveria recusar a escrita.'); }
        catch (QueryException $e) { $this->assertNotEmpty($e->getCode()); }
    }

    public function test_expansao_retomavel_preserva_dados_e_aplica_constraints(): void
    {
        $v = ['id' => 1, 'usuario_id' => 1, 'espaco_id' => 1, 'perfil' => 'admin_rede', 'situacao' => 'ativo', 'inicio_em' => now()];
        DB::table('usuario_vinculos')->insert($v);
        $migration = require database_path('migrations/2026_11_01_000001_expandir_usuario_vinculos.php');
        $migration->up();
        $this->assertDatabaseCount('usuario_vinculos', 1);
        $this->recusa(fn () => DB::table('usuario_vinculos')->insert(array_replace($v, ['id' => 2])));
        $this->recusa(fn () => DB::table('usuario_vinculos')->insert(array_replace($v, ['id' => 2, 'espaco_id' => 999])));
        $this->recusa(fn () => DB::table('usuario_vinculos')->insert(array_replace($v, ['id' => 2, 'perfil' => 'inexistente'])));
        $this->recusa(fn () => DB::table('usuarios')->where('id', 1)->delete());
        $this->recusa(fn () => DB::table('espacos')->where('id', 1)->delete());
        DB::table('vinculo_permissoes')->insert(['vinculo_id' => 1, 'permissao' => 'escola.ver', 'efeito' => 'retirar']);
        $this->recusa(fn () => DB::table('vinculo_permissoes')->insert(['vinculo_id' => 1, 'permissao' => 'inventada', 'efeito' => 'conceder']));
        DB::table('vinculo_eventos')->insert(['vinculo_id' => 1, 'acao' => 'ativacao', 'ocorrido_em' => now(), 'motivo_tecnico' => 'teste']);
        $this->recusa(fn () => DB::table('usuario_vinculos')->where('id', 1)->delete());
        $this->assertDatabaseCount('vinculo_eventos', 1);
    }

    public function test_restricoes_compostas_rejeitam_cruzamento_entre_espacos(): void
    {
        DB::table('nucleos')->insert(['id' => 1, 'rede_id' => 1, 'nome' => 'Núcleo']);
        foreach ([1, 2] as $id) {
            DB::table('escolas')->insert(['id' => $id, 'nucleo_id' => 1, 'nome' => 'Escola', 'inep' => '0000000'.$id, 'tipo_rede' => 'estadual']);
            DB::table('espacos')->insert(['id' => $id + 1, 'cliente_id' => 1, 'tipo' => 'escola', 'nome' => 'Escola', 'situacao' => 'ativo', 'escola_id' => $id]);
            DB::table('turmas')->insert(['id' => $id, 'escola_id' => $id, 'espaco_id' => $id + 1, 'nome' => '3A', 'serie' => '3', 'turno' => 'tarde', 'ano_letivo' => 2026]);
            DB::table('alunos')->insert(['id' => $id, 'turma_id' => $id, 'espaco_id' => $id + 1, 'nome' => 'Fictício', 'matricula' => 'B-'.$id]);
            DB::table('usuario_vinculos')->insert(['id' => $id, 'usuario_id' => 1, 'espaco_id' => $id + 1, 'perfil' => 'professor', 'situacao' => 'ativo', 'inicio_em' => now()]);
        }
        foreach (['turmas' => 'turma_id', 'alunos' => 'aluno_id'] as $pai => $campo) {
            DB::table('vinculo_'.$pai)->insert(['vinculo_id' => 1, 'espaco_id' => 2, $campo => 1]);
            $this->recusa(fn () => DB::table('vinculo_'.$pai)->insert(['vinculo_id' => 1, 'espaco_id' => 2, $campo => 2]));
            $this->recusa(fn () => DB::table('vinculo_'.$pai)->insert(['vinculo_id' => 1, 'espaco_id' => 3, $campo => 2]));
            $this->recusa(fn () => DB::table('vinculo_'.$pai)->insert(['vinculo_id' => 1, 'espaco_id' => 2, $campo => 1]));
            $this->recusa(fn () => DB::table($pai)->where('id', 1)->update(['espaco_id' => 3]));
            $this->recusa(fn () => DB::table($pai)->where('id', 1)->delete());
        }
        $this->recusa(fn () => DB::table('usuario_vinculos')->where('id', 1)->update(['espaco_id' => 3]));
        $migration = require database_path('migrations/2026_11_01_000002_restricoes_dos_vinculos.php');
        $migration->up();
        $this->assertDatabaseCount('vinculo_turmas', 1);
        $this->assertDatabaseCount('vinculo_alunos', 1);
    }

    public function test_conversao_transacional_repete_sem_duplicar_no_mariadb(): void
    {
        DB::table('usuario_escopos')->insert(['usuario_id' => 1, 'escopo_tipo' => 'rede', 'escopo_id' => 1]);
        $s = app(\App\Services\ConverterVinculosLegados::class);
        $m = $s->preparar();
        $m['usuarios'][0] = ['usuario_id' => 1, 'acao' => 'converter', 'motivo' => 'conversao_revisada', 'vinculos' => [
            ['espaco_id' => 1, 'turmas' => [], 'alunos' => [], 'ajustes' => [], 'reducoes_aceitas' => []],
        ]];
        $this->assertFalse($s->executar($m)['aplicado']);
        $this->assertSame($s->executar($m, true, 1), $s->executar($m, true, 1));
        $this->assertDatabaseCount('usuario_vinculos', 1);
        $this->assertDatabaseCount('vinculo_eventos', 1);
    }

    public function test_falha_tardia_desfaz_lote_no_mariadb(): void
    {
        DB::table('usuario_escopos')->insert(['usuario_id' => 1, 'escopo_tipo' => 'rede', 'escopo_id' => 1]);
        DB::table('usuarios')->insert(['id' => 2, 'perfil' => 'aluno', 'nome' => 'Fictício', 'email' => 'aluno@example.test', 'cpf' => '00000000002', 'password' => 'ficticio']);
        $s = app(\App\Services\ConverterVinculosLegados::class);
        $m = $s->preparar();
        $m['usuarios'][0] = ['usuario_id' => 1, 'acao' => 'converter', 'motivo' => 'conversao_revisada', 'vinculos' => [
            ['espaco_id' => 1, 'turmas' => [], 'alunos' => [], 'ajustes' => [], 'reducoes_aceitas' => []],
        ]];
        $m['usuarios'][1]['acao'] = 'converter';
        $m['usuarios'][1]['motivo'] = 'conversao_revisada';
        try { $s->executar($m, true, 1); $this->fail('A segunda conta deveria bloquear todo o lote.'); }
        catch (\RuntimeException $e) { $this->assertStringContainsString('pendências', $e->getMessage()); }
        $this->assertDatabaseCount('usuario_vinculos', 0);
        $this->assertDatabaseCount('vinculo_eventos', 0);
        $this->assertSame($m['fonte_sha256'], $s->fonte());
    }
}
