<?php

namespace Tests\Feature\Infrastructure;

use App\Models\Aplicacao;
use App\Models\AplicacaoAluno;
use App\Models\AplicacaoAplicador;
use App\Models\Cargo;
use App\Models\Disciplina;
use App\Models\Escola;
use App\Models\PeriodoLetivo;
use App\Models\Relatorio;
use App\Models\Resultado;
use App\Models\SerieAno;
use App\Models\TemaHabilidade;
use App\Models\User;
use App\Models\UsuarioDisciplina;
use App\Models\UsuarioLotacao;
use App\Policies\AplicacaoPolicy;
use App\Policies\RelatorioPolicy;
use App\Policies\ResultadoPolicy;
use Database\Seeders\AcademicCatalogSeeder;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class R3DomainFoundationTest extends TestCase
{
    use RefreshDatabase;

    public function test_every_canonical_module_has_a_persistent_source(): void
    {
        foreach ([
            'cargos',
            'usuario_lotacoes',
            'periodos_letivos',
            'series_anos',
            'disciplinas',
            'usuario_disciplinas',
            'arquivos',
            'responsaveis',
            'aluno_responsaveis',
            'temas_habilidades',
            'questao_temas',
            'aplicacoes',
            'aplicacao_aplicadores',
            'aplicacao_alunos',
            'cartoes_resposta',
            'leituras_cartao',
            'respostas_detectadas',
            'resultados',
            'resultado_questoes',
            'relatorios',
            'preferencias_usuario',
            'preferencias_notificacao',
            'solicitacoes_lgpd',
            'logs_sincronizacao',
        ] as $table) {
            $this->assertTrue(Schema::hasTable($table), "Missing R3 table: {$table}");
        }

        $this->assertTrue(Schema::hasColumns('turmas', ['periodo_letivo_id', 'serie_ano_id', 'capacidade']));
        $this->assertTrue(Schema::hasColumns('provas', ['disciplina_id', 'serie_ano_id', 'valor_total']));
        $this->assertTrue(Schema::hasColumns('alunos', ['nome_social', 'foto_arquivo_id']));
        $this->assertTrue(Schema::hasColumn('usuarios', 'foto_arquivo_id'));
    }

    public function test_canonical_models_expose_relationship_contracts(): void
    {
        $this->assertSame('usuario_lotacoes', (new Cargo)->lotacoes()->getRelated()->getTable());
        $this->assertSame('turmas', (new PeriodoLetivo)->turmas()->getRelated()->getTable());
        $this->assertSame('provas', (new SerieAno)->provas()->getRelated()->getTable());
        $this->assertSame('temas_habilidades', (new Disciplina)->temasHabilidades()->getRelated()->getTable());
        $this->assertSame('questoes', (new TemaHabilidade)->questoes()->getRelated()->getTable());
        $this->assertSame('aplicacao_alunos', (new Aplicacao)->alunos()->getRelated()->getTable());
        $this->assertSame('leituras_cartao', (new AplicacaoAluno)->leituras()->getRelated()->getTable());
        $this->assertSame('usuarios', (new AplicacaoAplicador)->usuario()->getRelated()->getTable());
    }

    public function test_academic_catalog_is_idempotent_and_separates_roles_from_permissions(): void
    {
        $this->seed(AcademicCatalogSeeder::class);
        $this->seed(AcademicCatalogSeeder::class);

        $this->assertDatabaseCount('cargos', 6);
        $this->assertDatabaseCount('series_anos', 9);
        $this->assertDatabaseCount('disciplinas', 14);
        $this->assertDatabaseHas('cargos', ['codigo' => 'coordenador-pedagogico']);
        $this->assertDatabaseHas('disciplinas', ['codigo' => 'matematica']);
        $this->assertDatabaseMissing('perfis', ['codigo' => 'coordenador-pedagogico']);
    }

    public function test_current_staff_and_academic_links_preserve_history_without_duplicate_active_link(): void
    {
        $lotacao = UsuarioLotacao::query()->create([
            'usuario_id' => User::factory()->create()->id,
            'cargo_id' => Cargo::query()->create([
                'codigo' => 'diretor',
                'nome' => 'Diretor',
                'ativo' => true,
            ])->id,
            'escola_id' => Escola::factory()->create()->id,
            'inicio_em' => now()->toDateString(),
        ]);

        $disciplineLink = UsuarioDisciplina::query()->create([
            'usuario_id' => $lotacao->usuario_id,
            'disciplina_id' => Disciplina::query()->create([
                'codigo' => 'matematica',
                'nome' => 'Matematica',
                'ativo' => true,
            ])->id,
            'escola_id' => $lotacao->escola_id,
            'inicio_em' => now()->toDateString(),
        ]);

        $this->assertNotNull($lotacao->refresh()->chave_vigente);
        $this->assertNotNull($disciplineLink->refresh()->chave_vigente);
        $this->expectException(QueryException::class);

        UsuarioDisciplina::query()->create([
            'usuario_id' => $disciplineLink->usuario_id,
            'disciplina_id' => $disciplineLink->disciplina_id,
            'escola_id' => $disciplineLink->escola_id,
            'inicio_em' => now()->toDateString(),
        ]);
    }

    public function test_operational_contracts_have_explicit_policies(): void
    {
        $this->assertInstanceOf(AplicacaoPolicy::class, Gate::getPolicyFor(Aplicacao::class));
        $this->assertInstanceOf(ResultadoPolicy::class, Gate::getPolicyFor(Resultado::class));
        $this->assertInstanceOf(RelatorioPolicy::class, Gate::getPolicyFor(Relatorio::class));
    }
}
