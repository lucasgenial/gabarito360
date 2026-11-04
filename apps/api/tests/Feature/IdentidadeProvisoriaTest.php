<?php

namespace Tests\Feature;

use App\Models\{Aluno, Usuario};
use App\Services\PrepararIdentidadesProvisorias;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class IdentidadeProvisoriaTest extends TestCase
{
    use RefreshDatabase;

    private function legado(): void
    {
        Usuario::create(['nome' => 'Pessoa fictícia', 'perfil' => 'professor', 'email' => 'provisorio@example.test',
            'cpf' => '00000000001', 'password' => 'ficticio']);
        $r = DB::table('redes')->insertGetId(['nome' => 'Rede fictícia', 'tipo' => 'estadual', 'uf' => 'BA']);
        $n = DB::table('nucleos')->insertGetId(['rede_id' => $r, 'nome' => 'Núcleo fictício']);
        $e = DB::table('escolas')->insertGetId(['nucleo_id' => $n, 'nome' => 'Escola fictícia', 'inep' => '29000001', 'tipo_rede' => 'estadual']);
        $t = DB::table('turmas')->insertGetId(['escola_id' => $e, 'nome' => 'Turma fictícia', 'serie' => '3', 'turno' => 'tarde', 'ano_letivo' => 2026]);
        foreach ([1, 2] as $i) Aluno::create(['nome' => 'Pessoa fictícia', 'matricula' => 'PROV-'.$i, 'turma_id' => $t]);
    }

    private function servico(): PrepararIdentidadesProvisorias { return app(PrepararIdentidadesProvisorias::class); }

    private function legadoBruto(): string
    {
        return json_encode([DB::table('usuarios')->orderBy('id')->get(), DB::table('alunos')->orderBy('id')->get()], JSON_THROW_ON_ERROR);
    }

    public function test_simulacao_e_padrao_nao_escreve_e_so_devolve_contagens(): void
    {
        $this->legado(); $antes = $this->legadoBruto(); $queries = [];
        DB::listen(function ($q) use (&$queries) { $queries[] = strtolower(ltrim($q->sql)); });
        $r = $this->servico()->executar();
        foreach ($queries as $sql) $this->assertStringStartsWith('select', $sql);
        $this->assertSame('simulacao_sem_escrita', $r['modo']);
        $this->assertSame(['usuarios' => 1, 'alunos' => 2], $r['pendentes']);
        $this->assertSame(0, $r['criados']);
        $this->assertStringNotContainsString('Pessoa fictícia', json_encode($r));
        $this->assertStringNotContainsString('provisorio@example.test', json_encode($r));
        $this->assertSame($antes, $this->legadoBruto());
        $this->assertDatabaseCount('pessoas', 0);
    }

    public function test_escrita_e_negada_sem_habilitacao_explicita(): void
    {
        $this->legado();
        $this->expectException(\RuntimeException::class);
        $this->servico()->executar(true);
    }

    public function test_lotes_retomam_sem_duplicar_e_preservam_todos_os_campos_legados(): void
    {
        $this->legado(); $antes = $this->legadoBruto(); config(['identidade.provisorios_ativos' => true]);
        $a = $this->servico()->executar(true, 2);
        $this->assertSame(2, $a['criados']); $this->assertSame(['usuarios' => 0, 'alunos' => 1], $a['pendentes']);
        $b = $this->servico()->executar(true, 2); $this->assertSame(1, $b['criados']);
        $this->assertSame(0, $this->servico()->executar(true, 2)['criados']);
        $this->assertDatabaseCount('pessoas', 3); $this->assertDatabaseCount('pessoa_usuarios', 1);
        $this->assertDatabaseCount('pessoa_alunos', 2); $this->assertDatabaseCount('pessoa_identidade_eventos', 3);
        $this->assertDatabaseCount('pessoa_documentos', 0);
        $this->assertSame(3, DB::table('pessoas')->where('situacao', 'provisoria')->count());
        $this->assertSame($antes, $this->legadoBruto());
    }

    public function test_homonimos_sem_cpf_nao_sao_fundidos_com_a_conta_ou_entre_si(): void
    {
        $this->legado(); config(['identidade.provisorios_ativos' => true]); $this->servico()->executar(true);
        $ids = array_merge(DB::table('pessoa_usuarios')->pluck('pessoa_id')->all(), DB::table('pessoa_alunos')->pluck('pessoa_id')->all());
        $this->assertCount(3, array_unique($ids));
        $this->assertSame(3, DB::table('pessoas')->distinct()->count('uuid'));
    }

    public function test_falha_de_evento_reverte_unidade_sem_pessoa_ou_mapa_orfao_e_retry_funciona(): void
    {
        $this->legado(); config(['identidade.provisorios_ativos' => true]); $falhar = true;
        DB::listen(function ($q) use (&$falhar) {
            if ($falhar && str_starts_with(strtolower(ltrim($q->sql)), 'insert') && str_contains($q->sql, 'pessoa_identidade_eventos')) {
                $falhar = false; throw new \RuntimeException('Falha fictícia de auditoria.');
            }
        });
        try { $this->servico()->executar(true); $this->fail('Falha de auditoria ignorada.'); }
        catch (\RuntimeException $e) { $this->assertSame('Falha fictícia de auditoria.', $e->getMessage()); }
        foreach (['pessoas', 'pessoa_usuarios', 'pessoa_alunos', 'pessoa_identidade_eventos'] as $t) $this->assertDatabaseCount($t, 0);
        $this->assertSame(3, $this->servico()->executar(true)['criados']);
    }

    public function test_mapas_historicos_nao_podem_ser_reescritos_ou_apagados(): void
    {
        $this->legado(); config(['identidade.provisorios_ativos' => true]); $this->servico()->executar(true);
        foreach (['pessoa_usuarios', 'pessoa_alunos'] as $t) {
            foreach (['update', 'delete'] as $op) {
                try {
                    $q = DB::table($t); $op === 'update' ? $q->update(['pessoa_id' => 99999]) : $q->delete();
                    $this->fail('Mapa histórico foi modificado.');
                } catch (QueryException) { $this->addToAssertionCount(1); }
            }
        }
        $this->assertDatabaseCount('pessoa_usuarios', 1); $this->assertDatabaseCount('pessoa_alunos', 2);
    }

    public function test_limites_e_cli_nao_transformam_simulacao_em_escrita(): void
    {
        $this->legado();
        foreach ([0, -1, 1001] as $lote) {
            try { $this->servico()->executar(false, $lote); $this->fail('Lote inválido aceito.'); }
            catch (\RuntimeException) { $this->addToAssertionCount(1); }
        }
        $this->artisan('g360:identidades-provisorias')->assertSuccessful();
        $this->artisan('g360:identidades-provisorias', ['--aplicar' => true])->assertFailed();
        $this->assertDatabaseCount('pessoas', 0);
    }
}
