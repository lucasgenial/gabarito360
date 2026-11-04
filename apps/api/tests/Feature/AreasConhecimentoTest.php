<?php

namespace Tests\Feature;

use App\Models\Usuario;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AreasConhecimentoTest extends TestCase
{
    use RefreshDatabase, \Tests\Support\CenarioDisciplinas;

    private function area(string $nome = 'Raciocínio e tecnologia'): int
    {
        return $this->postJson('/api/v1/areas-conhecimento', ['nome' => $nome, 'sigla' => 'RT', 'ativo' => true])->assertCreated()->json('data.id');
    }

    public function test_relacao_muitos_para_muitos_salva_junto_e_preserva_edicao_parcial(): void
    {
        $antes = DB::table('provas')->orderBy('id')->get()->toJson();
        $a = $this->area(); $b = $this->area('Ambiente e sociedade');
        $d = $this->postJson('/api/v1/disciplinas', ['nome' => 'Matemática', 'ativo' => true, 'area_ids' => [$a, $b]])->assertCreated()->assertJsonCount(2, 'data.areas')->json('data.id');
        $this->postJson('/api/v1/disciplinas', ['nome' => 'Geografia', 'ativo' => true, 'area_ids' => [$b]])->assertCreated();
        $this->assertDatabaseCount('disciplina_areas', 3);
        $this->putJson("/api/v1/disciplinas/$d", ['nome' => 'Matemática escolar', 'ativo' => true, 'versao' => 1])->assertOk()->assertJsonCount(2, 'data.areas');
        $this->getJson('/api/v1/disciplinas')->assertOk()->assertJsonPath('meta.total', 2);
        $this->putJson("/api/v1/disciplinas/$d", ['nome' => 'Matemática escolar', 'ativo' => true, 'versao' => 2, 'area_ids' => []])->assertOk()->assertJsonCount(0, 'data.areas');
        $this->assertDatabaseCount('disciplina_areas', 1);
        $evento = DB::table('disciplina_eventos')->where('entidade', 'disciplina')->where('entidade_id', $d)->orderByDesc('id')->first();
        $this->assertCount(2, json_decode($evento->antes, true)['areas']);
        $this->assertCount(0, json_decode($evento->depois, true)['areas']);
        $this->assertSame($antes, DB::table('provas')->orderBy('id')->get()->toJson());
    }

    public function test_inativa_preserva_relacoes_mas_recusa_novas_e_permite_reativar(): void
    {
        $a = $this->area();
        $d = $this->postJson('/api/v1/disciplinas', ['nome' => 'Matemática', 'ativo' => true, 'area_ids' => [$a]])->assertCreated()->json('data.id');
        $this->putJson("/api/v1/areas-conhecimento/$a", ['nome' => 'Raciocínio', 'ativo' => false, 'versao' => 1])->assertOk();
        $this->getJson("/api/v1/disciplinas/opcoes?disciplina_id=$d")->assertOk()->assertJsonCount(1, 'data.areas');
        $this->getJson('/api/v1/disciplinas/opcoes')->assertOk()->assertJsonCount(0, 'data.areas');
        $this->putJson("/api/v1/disciplinas/$d", ['nome' => 'Matemática', 'ativo' => true, 'versao' => 1, 'area_ids' => [$a]])->assertOk()->assertJsonCount(1, 'data.areas');
        $this->postJson('/api/v1/disciplinas', ['nome' => 'Física', 'ativo' => true, 'area_ids' => [$a]])->assertUnprocessable()->assertJsonValidationErrors('area_ids');
        $this->putJson("/api/v1/areas-conhecimento/$a", ['nome' => 'Raciocínio', 'ativo' => true, 'versao' => 2])->assertOk();
        $this->postJson('/api/v1/disciplinas', ['nome' => 'Física', 'ativo' => true, 'area_ids' => [$a]])->assertCreated();
    }

    public function test_escopo_permissoes_e_conflitos_nao_sobrescrevem_relacoes(): void
    {
        $a = $this->area();
        app('auth')->forgetGuards(); Sanctum::actingAs(Usuario::findOrFail(1)->noVinculo(2));
        $this->getJson('/api/v1/areas-conhecimento')->assertOk()->assertJsonPath('meta.total', 0);
        $this->getJson("/api/v1/areas-conhecimento/$a")->assertNotFound();
        $this->putJson("/api/v1/areas-conhecimento/$a", ['nome' => 'Outro', 'ativo' => true, 'versao' => 1])->assertNotFound();
        $this->postJson('/api/v1/disciplinas', ['nome' => 'Invasão', 'ativo' => true, 'area_ids' => [$a]])->assertUnprocessable();
        app('auth')->forgetGuards(); Sanctum::actingAs(Usuario::findOrFail(1)->noVinculo(1));
        $this->putJson("/api/v1/areas-conhecimento/$a", ['nome' => 'Novo nome', 'ativo' => true, 'versao' => 1])->assertOk();
        $this->putJson("/api/v1/areas-conhecimento/$a", ['nome' => 'Perdido', 'ativo' => false, 'versao' => 1])->assertConflict();
        $this->assertDatabaseHas('areas_conhecimento', ['id' => $a, 'nome' => 'Novo nome', 'ativo' => true]);
        foreach ([[2, 3], [1, 4]] as [$u, $v]) {
            app('auth')->forgetGuards(); Sanctum::actingAs(Usuario::findOrFail($u)->noVinculo($v));
            $this->getJson('/api/v1/areas-conhecimento')->assertOk()->assertJsonPath('data.gerir', false);
            $this->postJson('/api/v1/areas-conhecimento', ['nome' => 'Sem acesso', 'ativo' => true])->assertForbidden();
        }
        config(['disciplinas.ativo' => false]); $this->getJson('/api/v1/areas-conhecimento')->assertNotFound();
    }

    public function test_validacao_paginacao_e_nenhuma_classificacao_automatica(): void
    {
        $a = $this->area();
        $this->postJson('/api/v1/areas-conhecimento', ['nome' => ' RACIOCINIO E TECNOLOGIA ', 'ativo' => true])->assertUnprocessable()->assertJsonValidationErrors('nome');
        $this->postJson('/api/v1/areas-conhecimento', ['nome' => str_repeat('ß', 100), 'ativo' => true])->assertUnprocessable();
        $this->postJson('/api/v1/areas-conhecimento', ['nome' => 'Externa', 'ativo' => true, 'espaco_id' => 6])->assertUnprocessable();
        $this->postJson('/api/v1/disciplinas', ['nome' => 'Matemática', 'referencia' => 'matematica', 'ativo' => true])->assertCreated()->assertJsonCount(0, 'data.areas');
        $this->postJson('/api/v1/disciplinas', ['nome' => 'Repetida', 'ativo' => true, 'area_ids' => [$a, $a]])->assertUnprocessable();
        for ($i = 1; $i <= 21; $i++) $this->area('Área local '.$i);
        $this->getJson('/api/v1/areas-conhecimento?page=2')->assertOk()->assertJsonCount(2, 'data.areas')->assertJsonPath('meta.total', 22);
        $this->getJson('/api/v1/areas-conhecimento?busca=RT&ativo=1')->assertOk()->assertJsonPath('meta.total', 22);
        $this->getJson('/api/v1/areas-conhecimento?busca=inexistente')->assertOk()->assertJsonCount(0, 'data.areas');
    }

    public function test_chave_composta_impede_associar_area_de_outra_escola_no_banco(): void
    {
        $a = $this->area();
        DB::table('disciplinas')->insert(['id' => 91, 'espaco_id' => 6, 'nome' => 'Outra', 'nome_chave' => 'outra']);
        $this->expectException(\Illuminate\Database\QueryException::class);
        DB::table('disciplina_areas')->insert(['espaco_id' => 6, 'disciplina_id' => 91, 'area_id' => $a]);
    }
}
