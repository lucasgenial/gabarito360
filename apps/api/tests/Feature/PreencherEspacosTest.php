<?php

namespace Tests\Feature;

use App\Services\PreencherEspacosLegados;
use App\Support\EstruturaEspacos;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class PreencherEspacosTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        foreach (EstruturaEspacos::MANIFESTO as $rede => $nucleos) {
            DB::table('redes')->insert(['id' => $rede, 'nome' => 'Rede fictícia '.$rede, 'tipo' => 'estadual', 'uf' => 'BA']);
            foreach ($nucleos as $nucleo => $escolas) {
                DB::table('nucleos')->insert(['id' => $nucleo, 'rede_id' => $rede, 'nome' => 'Núcleo fictício']);
                foreach ($escolas as $escola) {
                    DB::table('escolas')->insert(['id' => $escola, 'nucleo_id' => $nucleo, 'nome' => 'Escola fictícia', 'inep' => str_pad((string) $escola, 8, '0', STR_PAD_LEFT), 'tipo_rede' => 'estadual']);
                }
            }
        }
        DB::table('usuarios')->insert(['id' => 1, 'nome' => 'Autor fictício', 'perfil' => 'admin_rede', 'email' => 't2@example.test', 'cpf' => '00000000001', 'password' => 'ficticio']);
        DB::table('turmas')->insert(['id' => 1, 'escola_id' => 8, 'nome' => '3A', 'serie' => '3', 'turno' => 'tarde', 'ano_letivo' => 2026]);
        DB::table('alunos')->insert(['id' => 1, 'turma_id' => 1, 'nome' => 'Aluno fictício', 'matricula' => 'T2-1', 'avatar_id' => 36]);
        DB::table('provas')->insert(['id' => 1, 'escola_id' => 8, 'criado_por' => 1, 'titulo' => 'Prova fictícia', 'disciplina' => 'Teste', 'serie' => '3', 'num_questoes' => 1, 'num_alternativas' => 4]);
        DB::table('prova_turmas')->insert(['prova_id' => 1, 'turma_id' => 1]);
        DB::table('gabaritos')->insert(['id' => 1, 'prova_id' => 1]);
        for ($i = 1; $i <= 205; $i++) {
            DB::table('gabarito_questoes')->insert(['gabarito_id' => 1, 'numero_questao' => $i, 'alternativa' => 'A', 'peso' => 1]);
        }
        DB::table('cartoes')->insert(['id' => 1, 'prova_id' => 1, 'aluno_id' => 1, 'imagem_url' => 'cartoes/ficticio.jpg']);
        DB::table('notas')->insert(['cartao_id' => 1, 'prova_id' => 1, 'aluno_id' => 1, 'turma_id' => 1, 'acertos' => 1, 'total_questoes' => 1, 'nota_final' => 10, 'status_aprovacao' => 'aprovado']);
    }

    public function test_simulacao_nao_cria_clientes_nem_preenche_dados(): void
    {
        $r = app(PreencherEspacosLegados::class)->executar();
        $this->assertFalse($r['aplicado']);
        $this->assertDatabaseCount('clientes', 0);
        $this->assertDatabaseCount('espacos', 0);
        $this->assertNull(DB::table('alunos')->value('espaco_id'));
        $this->artisan('g360:preencher-espacos --aplicar')->assertFailed();
    }

    public function test_preenchimento_repete_sem_duplicar_e_preserva_autoria_conteudo_e_permissoes(): void
    {
        $servico = app(PreencherEspacosLegados::class);
        $servico->executar(true);
        $antes = DB::table('espacos')->orderBy('id')->get()->toJson();
        $servico->executar(true);
        $this->assertSame($antes, DB::table('espacos')->orderBy('id')->get()->toJson());
        $this->assertDatabaseCount('clientes', 2);
        $this->assertDatabaseCount('espacos', 13);
        $e = DB::table('espacos')->where('escola_id', 8)->value('id');
        foreach (['turmas', 'alunos', 'provas', 'gabaritos', 'gabarito_questoes', 'prova_turmas', 'cartoes', 'notas'] as $tabela) {
            $this->assertSame(0, DB::table($tabela)->whereNull('espaco_id')->count());
            $this->assertSame(0, DB::table($tabela)->where('espaco_id', '<>', $e)->count());
        }
        $this->assertDatabaseCount('gabarito_questoes', 205);
        $this->assertDatabaseHas('alunos', ['id' => 1, 'criado_por' => null, 'origem_autoria' => 'legado_desconhecido', 'avatar_id' => 36, 'matricula' => 'T2-1']);
        $this->assertDatabaseHas('provas', ['id' => 1, 'criado_por' => 1, 'origem_autoria' => 'registrada']);
        $this->assertDatabaseHas('cartoes', ['id' => 1, 'imagem_url' => 'cartoes/ficticio.jpg']);
        $this->assertDatabaseHas('notas', ['nota_final' => 10]);
        $this->assertDatabaseCount('usuario_escopos', 0);
        $this->assertDatabaseCount('usuario_permissoes', 0);
    }

    public function test_divergencia_de_manifesto_bloqueia_antes_de_criar_cliente(): void
    {
        DB::table('escolas')->where('id', 8)->update(['nucleo_id' => 1]);
        try {
            app(PreencherEspacosLegados::class)->executar(true);
            $this->fail('Deveria rejeitar alteração de hierarquia');
        } catch (\RuntimeException $e) {
            $this->assertStringContainsString('manifesto', $e->getMessage());
        }
        $this->assertDatabaseCount('clientes', 0);
    }

    public function test_espaco_divergente_nao_e_sobrescrito_e_transacao_e_revertida(): void
    {
        DB::table('alunos')->where('id', 1)->update(['espaco_id' => 999999]);
        try {
            app(PreencherEspacosLegados::class)->executar(true);
            $this->fail('Deveria rejeitar espaço divergente');
        } catch (\RuntimeException $e) {
            $this->assertStringContainsString('divergente', $e->getMessage());
        }
        $this->assertDatabaseCount('clientes', 0);
        $this->assertDatabaseCount('espacos', 0);
        $this->assertNull(DB::table('turmas')->value('espaco_id'));
        $this->assertSame(999999, DB::table('alunos')->value('espaco_id'));
    }

    public function test_migracao_pode_ser_reexecutada_sem_apagar_dados(): void
    {
        $m = require database_path('migrations/2026_10_31_000006_expandir_clientes_e_espacos.php');
        $m->up();
        foreach (EstruturaEspacos::TABELAS as $t) {
            $this->assertTrue(Schema::hasColumns($t, ['espaco_id', 'criado_por', 'origem_autoria']));
        }
        $this->assertDatabaseCount('alunos', 1);
        $this->expectException(\RuntimeException::class);
        $m->down();
    }

    public function test_aplicacao_fora_de_testes_exige_manutencao(): void
    {
        $this->app['env'] = 'production';
        try {
            app(PreencherEspacosLegados::class)->executar(true);
            $this->fail('Deveria exigir manutenção');
        } catch (\RuntimeException $e) {
            $this->assertStringContainsString('manutenção', $e->getMessage());
            $this->assertDatabaseCount('clientes', 0);
        } finally {
            $this->app['env'] = 'testing';
        }
    }

    public function test_conciliacao_admin_simula_e_repete_duas_atribuicoes_sem_criar_vinculo_global(): void
    {
        app(PreencherEspacosLegados::class)->executar(true);
        $opcoes = ['--usuario' => 1, '--manifesto' => EstruturaEspacos::VERSAO];
        $this->artisan('g360:conciliar-admin-espacos', $opcoes)->assertSuccessful();
        $this->assertDatabaseCount('usuario_escopos', 0);
        $this->artisan('g360:conciliar-admin-espacos', $opcoes + ['--aplicar' => true])->assertSuccessful();
        $this->artisan('g360:conciliar-admin-espacos', $opcoes + ['--aplicar' => true])->assertSuccessful();
        $this->assertDatabaseCount('usuario_escopos', 2);
        foreach ([1, 2] as $rede) $this->assertDatabaseHas('usuario_escopos', ['usuario_id' => 1, 'escopo_tipo' => 'rede', 'escopo_id' => $rede]);
    }

    public function test_conciliacao_admin_recusa_alvo_errado_ambiguidade_e_acessos_divergentes(): void
    {
        app(PreencherEspacosLegados::class)->executar(true);
        $opcoes = ['--usuario' => 1, '--manifesto' => EstruturaEspacos::VERSAO, '--aplicar' => true];
        $this->artisan('g360:conciliar-admin-espacos', array_replace($opcoes, ['--usuario' => 999]))->assertFailed();
        DB::table('usuario_escopos')->insert(['usuario_id' => 1, 'escopo_tipo' => 'escola', 'escopo_id' => 1]);
        $this->artisan('g360:conciliar-admin-espacos', $opcoes)->assertFailed();
        $this->assertDatabaseCount('usuario_escopos', 1);
        DB::table('usuario_escopos')->delete();
        DB::table('usuarios')->insert(['nome' => 'Outro admin fictício', 'perfil' => 'admin_rede', 'email' => 'outro@example.test', 'cpf' => '00000000002', 'password' => 'teste']);
        $this->artisan('g360:conciliar-admin-espacos', $opcoes)->assertFailed();
        $this->assertDatabaseCount('usuario_escopos', 0);
    }
}
