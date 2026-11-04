<?php
namespace Tests\Feature;

use App\Models\Usuario;
use App\Services\PeriodosAreaDisciplina;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class PeriodosAreasTest extends TestCase
{
    use RefreshDatabase, \Tests\Support\CenarioDisciplinas;

    private function prepararVinculo(): array
    {
        $a=$this->postJson('/api/v1/areas-conhecimento',['nome'=>'Raciocínio','ativo'=>true])->assertCreated()->json('data.id');
        $d=$this->postJson('/api/v1/disciplinas',['nome'=>'Matemática','ativo'=>true])->assertCreated()->json('data.id');
        $this->postJson("/api/v1/areas-conhecimento/$a/vinculos",['disciplina_id'=>$d,'disciplina_versao'=>1,'versao'=>1])->assertOk();
        $p=DB::table('disciplina_area_vinculos')->first();
        return [$a,$d,$p];
    }

    public function test_encerrar_e_revincular_preserva_periodos_datas_e_dados_academicos(): void
    {
        $antes=DB::table('provas')->get()->toJson(); [$a,$d,$p]=$this->prepararVinculo();
        $this->assertSame('2026-11-04 15:00:00',$p->vinculado_em);
        $this->travel(1)->hours();
        $this->postJson("/api/v1/areas-conhecimento/$a/desvincular",['disciplina_id'=>$d,'disciplina_versao'=>2,'versao'=>2,'vinculo_id'=>$p->id])->assertOk();
        $this->assertDatabaseHas('disciplina_area_vinculos',['id'=>$p->id,'ativo'=>false,'vinculado_em'=>'2026-11-04 15:00:00','desvinculado_em'=>'2026-11-04 16:00:00']);
        $this->assertDatabaseCount('disciplina_areas',0);
        $this->postJson("/api/v1/areas-conhecimento/$a/vinculos",['disciplina_id'=>$d,'disciplina_versao'=>3,'versao'=>3])->assertOk();
        $this->assertDatabaseCount('disciplina_area_vinculos',2);
        $this->getJson("/api/v1/areas-conhecimento/$a")->assertOk()->assertJsonCount(2,'data.area.vinculos')->assertJsonCount(1,'data.area.disciplinas');
        $this->assertSame($antes,DB::table('provas')->get()->toJson());
        $this->assertDatabaseHas('disciplina_area_vinculos',['id'=>$p->id,'ativo'=>false]);
    }

    public function test_uso_conjunto_exige_confirmacao_e_nenhuma_entrada_em_lote_a_contorna(): void
    {
        [$a,$d,$p]=$this->prepararVinculo(); PeriodosAreaDisciplina::registrarUso(5,1,$p->id);
        $dados=['disciplina_id'=>$d,'disciplina_versao'=>2,'versao'=>2,'vinculo_id'=>$p->id];
        $r=$this->postJson("/api/v1/areas-conhecimento/$a/desvincular",$dados)->assertConflict()->assertJsonPath('codigo','confirmacao_desvinculacao');
        $token=$r->json('data.confirmacao');
        $this->assertDatabaseHas('areas_conhecimento',['id'=>$a,'versao'=>2]);
        $this->putJson("/api/v1/disciplinas/$d",['nome'=>'Alterada','ativo'=>true,'versao'=>2,'area_ids'=>[]])->assertConflict();
        $this->assertDatabaseHas('disciplinas',['id'=>$d,'nome'=>'Matemática','versao'=>2]);
        $this->putJson("/api/v1/areas-conhecimento/$a",['nome'=>'Alterada','ativo'=>true,'versao'=>2,'disciplina_ids'=>[]])->assertConflict();
        $this->postJson("/api/v1/areas-conhecimento/$a/desvincular",$dados+['confirmacao'=>$token])->assertOk();
        $this->assertDatabaseCount('prova_area_vinculos',1);
        $this->assertDatabaseHas('disciplina_area_vinculos',['id'=>$p->id,'ativo'=>false]);
        $this->postJson("/api/v1/areas-conhecimento/$a/desvincular",$dados+['confirmacao'=>$token])->assertConflict();
    }

    public function test_confirmacao_expirada_ou_uso_novo_exige_nova_conferencia(): void
    {
        [$a,$d,$p]=$this->prepararVinculo(); PeriodosAreaDisciplina::registrarUso(5,1,$p->id);
        $dados=['disciplina_id'=>$d,'disciplina_versao'=>2,'versao'=>2,'vinculo_id'=>$p->id];
        $token=$this->postJson("/api/v1/areas-conhecimento/$a/desvincular",$dados)->assertConflict()->json('data.confirmacao');
        $this->travel(11)->minutes();
        $this->postJson("/api/v1/areas-conhecimento/$a/desvincular",$dados+['confirmacao'=>$token])->assertConflict()->assertJsonPath('codigo','confirmacao_desvinculacao');
        $this->assertDatabaseHas('disciplina_area_vinculos',['id'=>$p->id,'ativo'=>true]);
        $prova=(array)DB::table('provas')->find(1); unset($prova['id']); $novo=DB::table('provas')->insertGetId($prova);
        $token=$this->postJson("/api/v1/areas-conhecimento/$a/desvincular",$dados)->assertConflict()->json('data.confirmacao');
        PeriodosAreaDisciplina::registrarUso(5,$novo,$p->id);
        $this->postJson("/api/v1/areas-conhecimento/$a/desvincular",$dados+['confirmacao'=>$token])->assertConflict()->assertJsonPath('data.provas',2);
    }

    public function test_escopo_e_perfis_impedem_gestao_e_historico_nao_autoriza_disciplinas(): void
    {
        [$a,$d,$p]=$this->prepararVinculo();
        app('auth')->forgetGuards(); Sanctum::actingAs(Usuario::findOrFail(2)->noVinculo(3));
        $this->getJson("/api/v1/areas-conhecimento/$a")->assertOk()->assertJsonCount(0,'data.area.vinculos');
        $this->postJson("/api/v1/areas-conhecimento/$a/desvincular",[])->assertForbidden();
        app('auth')->forgetGuards(); Sanctum::actingAs(Usuario::findOrFail(1)->noVinculo(2));
        $this->postJson("/api/v1/areas-conhecimento/$a/vinculos",['disciplina_id'=>$d,'disciplina_versao'=>2,'versao'=>2])->assertNotFound();
    }

    public function test_banco_impede_reescrever_periodo_encerrado(): void
    {
        [$a,$d,$p]=$this->prepararVinculo();
        $this->postJson("/api/v1/areas-conhecimento/$a/desvincular",['disciplina_id'=>$d,'disciplina_versao'=>2,'versao'=>2,'vinculo_id'=>$p->id])->assertOk();
        $this->expectException(\Illuminate\Database\QueryException::class);
        DB::table('disciplina_area_vinculos')->where('id',$p->id)->update(['ativo'=>true,'desvinculado_em'=>null]);
    }
}
