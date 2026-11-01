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
}
