<?php

namespace Tests\Feature;

use App\Models\Usuario;
use App\Support\Permissao;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class DisciplinasTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp(); config(['espacos.ativo' => true, 'disciplinas.ativo' => true]);
        $this->travelTo(\Carbon\Carbon::parse('2026-11-04 15:00:00', 'UTC')); Permissao::limparCache();
        foreach ([1, 2, 3] as $id) Usuario::forceCreate(['id' => $id, 'perfil' => 'professor', 'nome' => 'Fictício '.$id, 'email' => $id.'@example.test', 'cpf' => str_pad((string) $id, 11, '0', STR_PAD_LEFT), 'password' => 'ficticio', 'ativo' => true]);
        foreach ([1, 2] as $id) {
            DB::table('clientes')->insert(['id' => $id, 'tipo' => 'rede', 'nome' => 'Cliente', 'modalidade' => 'interno', 'situacao' => 'ativo']);
            DB::table('redes')->insert(['id' => $id, 'nome' => 'Rede', 'tipo' => 'estadual', 'uf' => 'BA']);
            DB::table('nucleos')->insert(['id' => $id, 'rede_id' => $id, 'nome' => 'Núcleo']);
            DB::table('escolas')->insert(['id' => $id, 'nucleo_id' => $id, 'nome' => 'Escola', 'inep' => '0000000'.$id, 'tipo_rede' => 'estadual']);
            foreach (['rede' => 0, 'nucleo' => 2, 'escola' => 4] as $tipo => $offset) DB::table('espacos')->insert(['id' => $id + $offset, 'cliente_id' => $id, 'tipo' => $tipo, 'nome' => 'Espaço', 'situacao' => 'ativo', $tipo.'_id' => $id]);
            DB::table('turmas')->insert(['id' => $id, 'escola_id' => $id, 'espaco_id' => $id + 4, 'nome' => '1A', 'serie' => '1', 'turno' => 'tarde', 'ano_letivo' => 2026]);
            DB::table('provas')->insert(['id' => $id, 'escola_id' => $id, 'espaco_id' => $id + 4, 'criado_por' => 1, 'titulo' => 'Legado', 'disciplina' => 'Matematica antiga', 'serie' => '1', 'num_questoes' => 1, 'num_alternativas' => 4]);
        }
        foreach ([[1, 1, 5, 'coordenador'], [2, 1, 6, 'coordenador'], [3, 2, 5, 'professor'], [4, 1, 5, 'dir_escolar'], [5, 3, 5, 'professor']] as [$id, $usuario, $espaco, $perfil])
            DB::table('usuario_vinculos')->insert(['id' => $id, 'usuario_id' => $usuario, 'espaco_id' => $espaco, 'perfil' => $perfil, 'situacao' => 'ativo', 'inicio_em' => now()->subDay(), 'versao_autorizacao' => 1]);
        DB::table('vinculo_turmas')->insert(['vinculo_id' => 3, 'espaco_id' => 5, 'turma_id' => 1]);
        $this->entrar();
    }

    private function entrar(int $usuario = 1, int $vinculo = 1): void
    {
        app('auth')->forgetGuards(); Sanctum::actingAs(Usuario::findOrFail($usuario)->noVinculo($vinculo));
    }

    private function disciplina(string $nome = 'Matemática'): int
    {
        return $this->postJson('/api/v1/disciplinas', ['nome' => $nome, 'sigla' => 'MAT', 'referencia' => 'matematica', 'ativo' => true])->assertCreated()->json('data.id');
    }

    private function preparar(): array
    {
        $d = $this->disciplina();
        $this->postJson("/api/v1/disciplinas/$d/professores", ['vinculo_id' => 3, 'ativo' => true])->assertOk();
        $c = $this->postJson('/api/v1/turmas/1/disciplinas', ['disciplina_id' => $d])->assertCreated()->json('data.id');
        return [$d, $c];
    }

    private function pedido(array $mais = []): array
    {
        return array_replace(['vinculo_id' => 3, 'inicio_em' => '2026-11-04', 'fim_em' => null, 'papel' => 'titular', 'chave' => (string) Str::uuid(), 'versao' => 1], $mais);
    }

    public function test_catalogo_isolado_versionado_paginado_sem_reescrever_prova(): void
    {
        $antes = DB::table('provas')->orderBy('id')->get()->toJson(); $d = $this->disciplina();
        $this->getJson('/api/v1/disciplinas?busca=MAT&ativo=1')->assertOk()->assertJsonCount(1, 'data.disciplinas')->assertJsonPath('meta.total', 1);
        $this->putJson("/api/v1/disciplinas/$d", ['nome' => 'Matemática escolar', 'ativo' => true, 'versao' => 1])->assertOk()->assertJsonPath('data.versao', 2);
        $this->putJson("/api/v1/disciplinas/$d", ['nome' => 'Sobrescrita', 'ativo' => true, 'versao' => 1])->assertConflict();
        $this->postJson('/api/v1/disciplinas', ['nome' => ' MATEMATICA ESCOLAR ', 'ativo' => true])->assertUnprocessable();
        $this->postJson('/api/v1/disciplinas', ['nome' => 'Estrangeira', 'ativo' => true, 'espaco_id' => 6])->assertUnprocessable();
        $this->postJson('/api/v1/disciplinas', ['nome' => 'Não oficial', 'ativo' => true, 'referencia' => 'inventada'])->assertUnprocessable();
        $this->entrar(1, 2);
        $this->getJson('/api/v1/disciplinas')->assertOk()->assertJsonPath('meta.total', 0);
        $this->getJson("/api/v1/disciplinas/$d")->assertNotFound();
        $this->postJson('/api/v1/turmas/2/disciplinas', ['disciplina_id' => $d])->assertNotFound();
        $this->assertSame($antes, DB::table('provas')->orderBy('id')->get()->toJson());
    }

    public function test_indicacao_nao_concede_turma_e_elegibilidade_e_revalidada_no_post(): void
    {
        [$d, $c] = $this->preparar();
        $this->postJson("/api/v1/disciplinas/$d/professores", ['vinculo_id' => 5, 'ativo' => true])->assertOk();
        $this->assertDatabaseMissing('vinculo_turmas', ['vinculo_id' => 5]);
        $this->getJson("/api/v1/turmas/1/disciplinas/$c")->assertOk()->assertJsonCount(1, 'data.elegiveis')->assertJsonMissingPath('data.elegiveis.0.email')->assertJsonMissingPath('data.elegiveis.0.cpf');
        $this->postJson("/api/v1/turmas/1/disciplinas/$c/atribuicoes", $this->pedido(['vinculo_id' => 5]))->assertUnprocessable();
        DB::table('vinculo_turmas')->where('vinculo_id', 3)->delete();
        $this->postJson("/api/v1/turmas/1/disciplinas/$c/atribuicoes", $this->pedido())->assertUnprocessable();
        $this->assertDatabaseCount('docente_atribuicoes', 0);
    }

    public function test_atribuicao_idempotente_sobreposicao_e_codocencia_explicita(): void
    {
        [$d, $c] = $this->preparar(); $p = $this->pedido();
        $a = $this->postJson("/api/v1/turmas/1/disciplinas/$c/atribuicoes", $p)->assertCreated()->json('data.id');
        $this->postJson("/api/v1/turmas/1/disciplinas/$c/atribuicoes", $p)->assertCreated()->assertJsonPath('data.id', $a);
        $this->postJson("/api/v1/turmas/1/disciplinas/$c/atribuicoes", array_replace($p, ['papel' => 'substituicao']))->assertConflict();
        $this->postJson("/api/v1/turmas/1/disciplinas/$c/atribuicoes", $this->pedido(['versao' => 2]))->assertUnprocessable();
        $this->postJson("/api/v1/disciplinas/$d/professores", ['vinculo_id' => 5, 'ativo' => true])->assertOk();
        DB::table('vinculo_turmas')->insert(['vinculo_id' => 5, 'espaco_id' => 5, 'turma_id' => 1]);
        $this->postJson("/api/v1/turmas/1/disciplinas/$c/atribuicoes", $this->pedido(['vinculo_id' => 5, 'versao' => 2]))->assertUnprocessable();
        $this->postJson("/api/v1/turmas/1/disciplinas/$c/atribuicoes", $this->pedido(['vinculo_id' => 5, 'versao' => 2, 'papel' => 'codocencia']))->assertCreated();
        $this->assertDatabaseCount('docente_atribuicoes', 2);
        $this->putJson("/api/v1/disciplinas/$d", ['nome' => 'Matemática', 'ativo' => false, 'versao' => 1])->assertUnprocessable();
        $this->postJson("/api/v1/disciplinas/$d/professores", ['vinculo_id' => 3, 'ativo' => false, 'versao' => 1])->assertUnprocessable();
    }

    public function test_professor_so_le_os_proprios_dados_diretor_le_sem_gerir(): void
    {
        [$d, $c] = $this->preparar(); $this->postJson("/api/v1/turmas/1/disciplinas/$c/atribuicoes", $this->pedido())->assertCreated();
        $outro = $this->disciplina('Arte');
        $this->entrar(2, 3);
        $this->getJson('/api/v1/disciplinas')->assertOk()->assertJsonCount(1, 'data.disciplinas')->assertJsonPath('data.gerir', false);
        $this->getJson("/api/v1/disciplinas/$outro")->assertNotFound();
        $this->getJson("/api/v1/turmas/1/disciplinas/$c")->assertOk()->assertJsonCount(1, 'data.componente.atribuicoes')->assertJsonCount(0, 'data.elegiveis');
        $this->postJson('/api/v1/disciplinas', ['nome' => 'Invasão', 'ativo' => true])->assertForbidden();
        $this->getJson('/api/v1/turmas/2/disciplinas')->assertNotFound();
        $this->entrar(1, 4);
        $this->getJson('/api/v1/disciplinas')->assertOk()->assertJsonCount(2, 'data.disciplinas')->assertJsonPath('data.gerir', false);
        $this->postJson("/api/v1/turmas/1/disciplinas/$c/atribuicoes", $this->pedido())->assertForbidden();
    }

    public function test_vigencia_retirada_e_bloqueio_do_modulo_sao_respeitados(): void
    {
        [$d, $c] = $this->preparar();
        DB::table('usuario_vinculos')->where('id', 3)->update(['situacao' => 'suspenso']);
        $this->postJson("/api/v1/turmas/1/disciplinas/$c/atribuicoes", $this->pedido())->assertUnprocessable();
        DB::table('usuario_vinculos')->where('id', 3)->update(['situacao' => 'ativo', 'fim_em' => '2026-11-10 03:00:00']);
        $this->postJson("/api/v1/turmas/1/disciplinas/$c/atribuicoes", $this->pedido())->assertUnprocessable();
        $this->postJson("/api/v1/turmas/1/disciplinas/$c/atribuicoes", $this->pedido(['fim_em' => '2026-11-10']))->assertCreated();
        DB::table('vinculo_permissoes')->insert(['vinculo_id' => 1, 'permissao' => 'disciplina.gerir', 'efeito' => 'retirar']);
        $this->postJson('/api/v1/disciplinas', ['nome' => 'Bloqueada', 'ativo' => true])->assertForbidden();
        config(['disciplinas.ativo' => false]); $this->getJson('/api/v1/disciplinas')->assertNotFound();
    }

    public function test_periodos_cancelamento_e_historico_imutavel(): void
    {
        [$d, $c] = $this->preparar();
        $this->postJson("/api/v1/turmas/1/disciplinas/$c/atribuicoes", $this->pedido(['inicio_em' => '2026-11-03']))->assertUnprocessable();
        $a = $this->postJson("/api/v1/turmas/1/disciplinas/$c/atribuicoes", $this->pedido(['inicio_em' => '2026-11-06']))->assertCreated()->json('data.id');
        $this->postJson("/api/v1/turmas/1/disciplinas/$c/atribuicoes/$a/encerrar", ['versao' => 1, 'acao' => 'encerrar'])->assertUnprocessable();
        $this->postJson("/api/v1/turmas/1/disciplinas/$c/atribuicoes/$a/encerrar", ['versao' => 1, 'acao' => 'cancelar'])->assertOk()->assertJsonPath('data.situacao', 'cancelada');
        $b = $this->postJson("/api/v1/turmas/1/disciplinas/$c/atribuicoes", $this->pedido(['versao' => 3]))->assertCreated()->json('data.id');
        $this->postJson("/api/v1/turmas/1/disciplinas/$c/atribuicoes/$b/encerrar", ['versao' => 1, 'acao' => 'encerrar'])->assertOk()->assertJsonPath('data.fim_em', '2026-11-04');
        $this->assertDatabaseCount('disciplina_eventos', 7);
        $this->expectException(\Illuminate\Database\QueryException::class);
        DB::table('disciplina_eventos')->update(['acao' => 'reescrita']);
    }

    public function test_chaves_compostas_recusam_professor_de_outra_escola_no_banco(): void
    {
        $d = $this->disciplina();
        $this->expectException(\Illuminate\Database\QueryException::class);
        DB::table('disciplina_professores')->insert(['espaco_id' => 6, 'disciplina_id' => $d, 'vinculo_id' => 2]);
    }

    public function test_inativacao_na_turma_preserva_catalogo_e_recusa_atribuicoes_abertas(): void
    {
        [$d,$c] = $this->preparar();
        $this->postJson("/api/v1/turmas/1/disciplinas/$c/situacao", ['ativo'=>false,'versao'=>1])->assertOk();
        $this->assertDatabaseHas('disciplinas', ['id'=>$d,'ativo'=>true]);
        $this->postJson("/api/v1/turmas/1/disciplinas/$c/atribuicoes", $this->pedido(['versao'=>2]))->assertUnprocessable();
        $this->postJson("/api/v1/turmas/1/disciplinas/$c/situacao", ['ativo'=>true,'versao'=>2])->assertOk();
        $this->postJson("/api/v1/turmas/1/disciplinas/$c/atribuicoes", $this->pedido(['versao'=>3]))->assertCreated();
        $this->postJson("/api/v1/turmas/1/disciplinas/$c/situacao", ['ativo'=>false,'versao'=>4])->assertUnprocessable();
        $this->assertDatabaseCount('turma_disciplinas',1);
    }
}
