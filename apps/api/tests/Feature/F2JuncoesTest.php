<?php

namespace Tests\Feature;

use App\Models\Usuario;
use App\Support\ConsultasPedagogicas;
use Illuminate\Database\Query\Expression;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

final class F2JuncoesTest extends TestCase
{
    use RefreshDatabase;
    use \Tests\Support\CenarioDisciplinas { setUp as private iniciarCenario; }

    protected function setUp(): void
    {
        $this->iniciarCenario();
        config(['vinculos.obrigatorio' => true, 'disciplinas.ativo' => false]);
        DB::table('provas')->where('id', 1)->update(['perfil_criacao' => 'coordenador', 'status' => 'publicada']);
        DB::table('prova_turmas')->insert(['prova_id' => 1, 'turma_id' => 1, 'espaco_id' => 5]);
        foreach ([1, 2] as $id) {
            DB::table('alunos')->insert(['id' => $id, 'espaco_id' => 5, 'turma_id' => 1, 'nome' => 'Fictício '.$id, 'matricula' => 'J-'.$id]);
            DB::table('cartoes')->insert(['id' => $id, 'espaco_id' => 5, 'prova_id' => 1, 'aluno_id' => $id, 'turma_aplicacao_id' => 1, 'status' => 'lido']);
            DB::table('notas')->insert(['id' => $id, 'espaco_id' => 5, 'prova_id' => 1, 'aluno_id' => $id, 'turma_id' => 1,
                'cartao_id' => $id, 'acertos' => 1, 'total_questoes' => 1, 'nota_final' => 10, 'status_aprovacao' => 'aprovado']);
        }
        // Existe nota na outra escola: o LEFT deve preservar a origem sem expor seu valor.
        DB::table('prova_turmas')->insert(['prova_id' => 2, 'turma_id' => 2, 'espaco_id' => 6]);
        DB::table('alunos')->insert(['id' => 3, 'espaco_id' => 6, 'turma_id' => 2, 'nome' => 'Outra escola fictícia', 'matricula' => 'J-3']);
        DB::table('cartoes')->insert(['id' => 3, 'espaco_id' => 6, 'prova_id' => 2, 'aluno_id' => 3, 'turma_aplicacao_id' => 2, 'status' => 'lido']);
        DB::table('notas')->insert(['id' => 3, 'espaco_id' => 6, 'prova_id' => 2, 'aluno_id' => 3, 'turma_id' => 2,
            'cartao_id' => 3, 'acertos' => 1, 'total_questoes' => 1, 'nota_final' => 9, 'status_aprovacao' => 'aprovado']);
    }

    public function test_chave_obrigatoria_preserva_duas_notas_iguais_e_left_sem_correspondencia(): void
    {
        $c = new ConsultasPedagogicas(Usuario::findOrFail(1)->noVinculo(1));
        $q = $c->paraJuncao('notas', ['prova_id', 'nota_final']);
        $linhas = $q->orderBy('notas.id')->get();
        $this->assertSame([1, 2], $linhas->pluck('id')->map(fn ($id) => (int) $id)->all());
        $this->assertSame(['id', 'prova_id', 'nota_final'], array_keys((array) $linhas[0]));
        $r = DB::table('provas as origem')->leftJoinSub($c->paraJuncao('notas', ['prova_id', 'nota_final']), 'restrito', 'restrito.prova_id', '=', 'origem.id')
            ->select('origem.id')->selectRaw('COUNT(restrito.id) as quantidade, AVG(restrito.nota_final) as media')
            ->groupBy('origem.id')->orderBy('origem.id')->get();
        $this->assertSame([2, 0], $r->pluck('quantidade')->map(fn ($n) => (int) $n)->all());
        $this->assertSame(10.0, (float) $r[0]->media);
        $this->assertNull($r[1]->media);
    }

    public static function revogacoes(): array
    {
        return [
            'conta' => ['usuarios', 1, 'ativo', false],
            'vinculo' => ['usuario_vinculos', 1, 'situacao', 'encerrado'],
            'cliente' => ['clientes', 1, 'situacao', 'encerrado'],
            'espaco' => ['espacos', 5, 'situacao', 'suspenso'],
            'pai' => ['espacos', 3, 'situacao', 'suspenso'],
        ];
    }

    #[DataProvider('revogacoes')]
    public function test_projecao_compilada_clonada_e_embutida_revalida_sem_refazer_ator(string $tabela, int $id, string $campo, mixed $valor): void
    {
        $ator = Usuario::findOrFail(1)->noVinculo(1);
        $c = new ConsultasPedagogicas($ator);
        $q = $c->paraJuncao('notas', ['prova_id', 'nota_final']);
        $q->toSql();
        $clone = clone $q;
        $join = DB::table('provas as origem')->leftJoinSub(clone $q, 'restrito', 'restrito.prova_id', '=', 'origem.id')
            ->orderBy('origem.id')->orderBy('restrito.id')->select('origem.id', 'restrito.id as nota_id');
        $join->toSql();
        $antes = DB::table($tabela)->where('id', $id)->value($campo);
        foreach ([false, true, false] as $revogar) {
            DB::table($tabela)->where('id', $id)->update([$campo => $revogar ? $valor : $antes]);
            foreach ([$q, $clone] as $consulta) {
                $this->assertSame($revogar ? [] : [1, 2], $consulta->orderBy('notas.id')->get()->pluck('id')->map(fn ($n) => (int) $n)->all());
            }
            $linhas = $join->get();
            $this->assertSame($revogar ? [null, null] : [1, 2, null], $linhas->pluck('nota_id')->map(fn ($n) => $n === null ? null : (int) $n)->all());
        }
    }

    public function test_apenas_identificadores_simples_de_codigo_sao_aceitos(): void
    {
        $c = new ConsultasPedagogicas(Usuario::findOrFail(1)->noVinculo(1));
        foreach ([['notas as n', ['nota_final']], ['notas', ['*']], ['notas', ['nota_final as valor']],
            ['notas', ['notas.nota_final']], ['notas', [new Expression('1')]], ['notas', [null]]] as [$tabela, $campos]) {
            try {
                $c->paraJuncao($tabela, $campos);
                $this->fail('Projeção inválida aceita.');
            } catch (\InvalidArgumentException $e) {
                $this->assertSame('Identificador de projeção inválido.', $e->getMessage());
            }
        }
        $this->assertCount(2, (array) $c->paraJuncao('notas', ['id', 'id', 'nota_final'])->first());
    }
}
