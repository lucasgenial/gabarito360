<?php

namespace Tests\Feature;

use App\Services\InventarioEspacos;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class InventarioEspacosTest extends TestCase
{
    use RefreshDatabase;

    private function escola(string $inep): array
    {
        $rede = DB::table('redes')->insertGetId(['nome' => 'Instituição confidencial', 'tipo' => 'estadual', 'uf' => 'BA']);
        $nucleo = DB::table('nucleos')->insertGetId(['rede_id' => $rede, 'nome' => 'Núcleo confidencial']);
        $escola = DB::table('escolas')->insertGetId(['nucleo_id' => $nucleo, 'nome' => 'Escola confidencial', 'inep' => $inep, 'tipo_rede' => 'estadual']);
        $turma = DB::table('turmas')->insertGetId(['escola_id' => $escola, 'nome' => 'Turma confidencial', 'serie' => '3', 'turno' => 'tarde', 'ano_letivo' => 2026]);
        return compact('rede', 'nucleo', 'escola', 'turma');
    }

    private function usuario(): int
    {
        return DB::table('usuarios')->insertGetId(['nome' => 'Pessoa confidencial', 'email' => 'sigilo@example.test', 'cpf' => '00000000123', 'password' => 'segredo-ficticio', 'perfil' => 'admin_rede', 'ativo' => true]);
    }

    private function prova(int $escola, int $usuario): int
    {
        return DB::table('provas')->insertGetId(['escola_id' => $escola, 'criado_por' => $usuario, 'titulo' => 'Conteúdo confidencial', 'disciplina' => 'Matemática', 'serie' => '3', 'num_questoes' => 1, 'num_alternativas' => 4]);
    }

    public function test_inventario_vazio_nao_autoriza_migracao(): void
    {
        $r = app(InventarioEspacos::class)->gerar();
        $this->assertSame(0, $r['ocorrencias']);
        $this->assertFalse($r['migracao_autorizada']);
        $this->artisan('g360:inventariar-espacos')->assertSuccessful();
    }

    public function test_mapeia_escola_sem_expor_conteudo_e_executa_apenas_selects(): void
    {
        $e = $this->escola('29000001');
        $u = $this->usuario();
        $this->prova($e['escola'], $u);
        DB::table('alunos')->insert(['turma_id' => $e['turma'], 'nome' => 'Aluno confidencial', 'matricula' => 'SEGREDO-MATRICULA', 'data_nascimento' => '2010-01-01']);
        DB::table('usuario_escopos')->insert(['usuario_id' => $u, 'escopo_tipo' => 'rede', 'escopo_id' => $e['rede']]);
        DB::enableQueryLog();
        DB::flushQueryLog();
        $r = app(InventarioEspacos::class)->gerar();
        $consultas = DB::getQueryLog();
        DB::disableQueryLog();
        foreach ($consultas as $consulta) {
            $this->assertMatchesRegularExpression('/^select\b/i', $consulta['query']);
        }
        $this->assertSame(0, $r['ocorrencias']);
        $this->assertSame([['escola_id' => $e['escola'], 'quantidade' => 1]], $r['por_escola']['alunos']);
        $json = json_encode($r);
        foreach (['confidencial', 'SEGREDO-MATRICULA', 'sigilo@example.test', '00000000123', 'segredo-ficticio', '2010-01-01'] as $segredo) {
            $this->assertStringNotContainsString($segredo, $json);
        }
        $this->assertSame(1, DB::table('alunos')->count());
        $this->assertSame(1, $r['pendencias']['administradores_rede_a_conciliar']);
    }

    public function test_detecta_relacoes_validas_por_fk_mas_cruzadas_entre_escolas(): void
    {
        $a = $this->escola('29000001');
        $b = $this->escola('29000002');
        $u = $this->usuario();
        $p = $this->prova($a['escola'], $u);
        $p2 = $this->prova($b['escola'], $u);
        $aluno = DB::table('alunos')->insertGetId(['turma_id' => $b['turma'], 'nome' => 'Aluno fictício', 'matricula' => 'FICTICIO', 'data_nascimento' => '2010-01-01']);
        DB::table('prova_turmas')->insert(['prova_id' => $p, 'turma_id' => $b['turma']]);
        $cartao = DB::table('cartoes')->insertGetId(['prova_id' => $p, 'aluno_id' => $aluno]);
        DB::table('notas')->insert(['cartao_id' => $cartao, 'prova_id' => $p2, 'aluno_id' => $aluno, 'turma_id' => $a['turma'], 'acertos' => 1, 'total_questoes' => 1, 'nota_final' => 10, 'status_aprovacao' => 'aprovado']);
        DB::table('usuario_escopos')->insert(['usuario_id' => $u, 'escopo_tipo' => 'escola', 'escopo_id' => 999999]);
        DB::table('sincronizacoes_seges')->insert(['status' => 'sucesso', 'detalhes' => '{"segredo":"nao-ler"}']);
        $r = app(InventarioEspacos::class)->gerar();
        $this->assertSame(1, $r['inconsistencias']['prova_turmas_entre_escolas']);
        $this->assertSame(1, $r['inconsistencias']['cartoes_com_aluno_de_outra_escola']);
        $this->assertSame(1, $r['inconsistencias']['notas_divergentes_do_cartao']);
        $this->assertSame(1, $r['inconsistencias']['notas_com_turma_de_outra_escola']);
        $this->assertSame(1, $r['inconsistencias']['escopos_escola_inexistentes']);
        $this->assertSame(1, $r['pendencias']['sincronizacoes_sem_destino_estruturado']);
        $this->artisan('g360:inventariar-espacos')->assertExitCode(2);
    }

    public function test_cartao_sem_aluno_e_permitido_e_falha_nao_expoe_detalhes(): void
    {
        $e = $this->escola('29000001');
        $p = $this->prova($e['escola'], $this->usuario());
        DB::table('cartoes')->insert(['prova_id' => $p, 'aluno_id' => null]);
        $this->assertSame(0, app(InventarioEspacos::class)->gerar()['ocorrencias']);
        $this->mock(InventarioEspacos::class, function ($mock) {
            $mock->shouldReceive('gerar')->once()->andThrow(new \RuntimeException('SQL segredo@example.test'));
        });
        $this->artisan('g360:inventariar-espacos')
            ->expectsOutput('Não foi possível concluir o inventário. Nenhuma migração foi executada. Confira conexão e versão do esquema.')
            ->doesntExpectOutputToContain('segredo@example.test')->assertExitCode(1);
    }
}
