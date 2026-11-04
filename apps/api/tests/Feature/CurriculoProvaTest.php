<?php
namespace Tests\Feature;

use App\Models\Usuario;
use App\Services\GestaoDisciplinas;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class CurriculoProvaTest extends TestCase
{
    use \Tests\Support\BancoCurriculoDescartavel, \Tests\Support\CenarioDisciplinas;

    private function entrarComo(int $usuario=1,int $vinculo=1): void
    {
        app('auth')->forgetGuards(); Sanctum::actingAs(Usuario::findOrFail($usuario)->noVinculo($vinculo));
    }

    private function preparar(): array
    {
        $u=Usuario::findOrFail(1)->noVinculo(1);$g=app(GestaoDisciplinas::class);
        DB::table('turmas')->insert(['id'=>3,'espaco_id'=>5,'escola_id'=>1,'nome'=>'1B','serie'=>'1','turno'=>'tarde','ano_letivo'=>2026]);
        DB::table('vinculo_turmas')->insert([['vinculo_id'=>3,'espaco_id'=>5,'turma_id'=>3],['vinculo_id'=>5,'espaco_id'=>5,'turma_id'=>1]]);
        $mat=$g->salvar($u,['nome'=>'Matemática','sigla'=>'MAT','ativo'=>true]);
        $fis=$g->salvar($u,['nome'=>'Física','sigla'=>'FIS','ativo'=>true]);
        $g->indicar($u,$mat->id,['vinculo_id'=>3,'ativo'=>true]);$g->indicar($u,$fis->id,['vinculo_id'=>5,'ativo'=>true]);
        foreach([$mat,$fis] as $d) foreach([1,3] as $turma) {
            $c=$g->adicionarComponente($u,$turma,$d->id);
            if($d->id===$mat->id || $turma===1) $g->atribuir($u,$turma,$c->id,['vinculo_id'=>$d->id===$mat->id?3:5,'inicio_em'=>'2026-11-04','fim_em'=>null,'papel'=>'titular','chave'=>(string)Str::uuid(),'versao'=>1]);
        }
        return [(int)$mat->id,(int)$fis->id];
    }

    private function dados(array $disciplinas,array $turmas=[1,3]): array
    {
        return ['escola_id'=>1,'titulo'=>'Avaliação curricular de teste','serie'=>'1','num_questoes'=>1,'num_alternativas'=>4,'turma_ids'=>$turmas,'disciplina_ids'=>$disciplinas];
    }

    private function criar(array $disciplinas,array $turmas=[1,3]): int
    {
        return $this->postJson('/api/v1/provas',$this->dados($disciplinas,$turmas))->assertCreated()->json('data.id');
    }

    private function gabarito(int $prova): int
    {
        $id=DB::table('gabaritos')->insertGetId(['prova_id'=>$prova,'espaco_id'=>5]);
        DB::table('gabarito_questoes')->insert(['gabarito_id'=>$id,'espaco_id'=>5,'numero_questao'=>1,'alternativa'=>'A','peso'=>1,'anulada'=>false]);
        return $id;
    }

    public function test_criacao_multidisciplinar_por_id_preserva_dados_anteriores_e_guarda_uso_conjunto(): void
    {
        [$m,$f]=$this->preparar(); $antes=DB::table('provas')->orderBy('id')->get()->toJson();
        $area=$this->postJson('/api/v1/areas-conhecimento',['nome'=>'Área de teste','ativo'=>true,'disciplina_ids'=>[$m,$f],'disciplina_versoes'=>DB::table('disciplinas')->pluck('versao','id')->all()])->assertCreated()->json('data.id');
        $id=$this->criar([$m,$f]);
        $this->getJson("/api/v1/provas/$id")->assertOk()->assertJsonCount(2,'data.curriculo.disciplinas')->assertJsonPath('data.disciplina','Multidisciplinar')->assertJsonCount(2,'data.turmas');
        $this->assertDatabaseCount('prova_area_vinculos',2);
        $this->assertSame($antes,DB::table('provas')->where('id','!=',$id)->orderBy('id')->get()->toJson());
        $p=DB::table('disciplina_areas')->where('disciplina_id',$m)->first();
        $versao=DB::table('disciplinas')->where('id',$m)->value('versao');
        $this->postJson("/api/v1/areas-conhecimento/$area/desvincular",['disciplina_id'=>$m,'disciplina_versao'=>$versao,'vinculo_id'=>$p->vinculo_id,'versao'=>1])->assertConflict()->assertJsonPath('codigo','confirmacao_desvinculacao')->assertJsonPath('data.provas',1);
    }

    public function test_professor_precisa_de_disciplina_em_todas_as_turmas_e_area_nao_concede_acesso(): void
    {
        [$m,$f]=$this->preparar(); $id=$this->criar([$m]);
        $this->postJson('/api/v1/areas-conhecimento',['nome'=>'Área comum','ativo'=>true,'disciplina_ids'=>[$m,$f],'disciplina_versoes'=>DB::table('disciplinas')->pluck('versao','id')->all()])->assertCreated();
        $this->entrarComo(3,5);
        $this->getJson("/api/v1/provas/$id")->assertNotFound();
        $this->entrarComo(2,3);
        $this->getJson("/api/v1/provas/$id")->assertOk()->assertJsonPath('data.pode_editar',false)->assertJsonPath('data.pode_copiar',false);
        $propria=$this->criar([$m]);
        $this->postJson('/api/v1/provas',$this->dados([$m,$f]))->assertUnprocessable()->assertJsonValidationErrors('disciplina_ids');
        $this->entrarComo(3,5);$this->getJson("/api/v1/provas/$propria")->assertNotFound();
        $this->entrarComo();$this->getJson("/api/v1/provas/$propria")->assertOk();
        $this->entrarComo(2,3); DB::table('docente_atribuicoes')->where('vinculo_id',3)->update(['situacao'=>'encerrada','fim_em'=>'2026-11-04']);
        $this->getJson("/api/v1/provas/$id")->assertNotFound();
        $this->getJson("/api/v1/provas/$propria")->assertOk();
    }

    public function test_opcoes_preservam_rascunho_invalido_sem_revelar_nome_de_outra_escola(): void
    {
        [$m,$f]=$this->preparar();
        DB::table('disciplinas')->insert(['id'=>90,'espaco_id'=>6,'nome'=>'Nome estrangeiro secreto','nome_chave'=>'secreta']);
        $this->postJson('/api/v1/provas/opcoes-disciplinas',['turma_ids'=>[1,3]])->assertOk()->assertJsonCount(2,'data.opcoes');
        $this->assertDatabaseCount('prova_curriculos',0);
        $this->entrarComo(2,3);
        $this->getJson('/api/v1/provas/opcoes-disciplinas?'.http_build_query(['turma_ids'=>[1,3],'rascunho_ids'=>[$m,$f,90]]))->assertOk()
            ->assertJsonCount(1,'data.opcoes')->assertJsonCount(3,'data.selecionadas')->assertJsonPath('data.selecionadas.0.elegivel',true)
            ->assertJsonPath('data.selecionadas.1.elegivel',false)->assertJsonPath('data.selecionadas.2.nome','Disciplina indisponível')->assertDontSee('Nome estrangeiro secreto');
        DB::table('vinculo_turmas')->where('vinculo_id',3)->where('turma_id',3)->delete();
        $this->postJson('/api/v1/provas',$this->dados([$m]))->assertForbidden();
        $this->assertDatabaseCount('prova_curriculos',0);
    }

    public function test_titulo_nao_reconfigura_e_versao_antiga_nao_sobrescreve_curriculo(): void
    {
        [$m,$f]=$this->preparar();$id=$this->criar([$m]);
        $antes=DB::table('prova_curriculos')->get()->toJson();
        $this->putJson("/api/v1/provas/$id",['titulo'=>'Título revisto'])->assertOk();
        $this->assertSame($antes,DB::table('prova_curriculos')->get()->toJson());
        $this->putJson("/api/v1/provas/$id",['disciplina_ids'=>[$f],'turma_ids'=>[1,3],'curriculo_versao'=>1])->assertOk();
        $this->putJson("/api/v1/provas/$id",['titulo'=>'Não sobrescrever','disciplina_ids'=>[$m],'turma_ids'=>[1],'curriculo_versao'=>1])->assertConflict();
        $this->assertDatabaseHas('provas',['id'=>$id,'titulo'=>'Título revisto','disciplina'=>'Física']);
        $this->assertDatabaseHas('prova_curriculos',['prova_id'=>$id,'versao'=>2]);
        $this->assertDatabaseCount('prova_turmas',2);
    }

    public function test_todas_as_rotas_de_publicacao_revalidam_e_nao_publicam_gabarito_ao_falhar(): void
    {
        [$m,$f]=$this->preparar();$this->entrarComo(2,3);$id=$this->criar([$m]);$gab=$this->gabarito($id);
        DB::table('disciplinas')->where('id',$m)->update(['ativo'=>false]);
        $this->postJson("/api/v1/provas/$id/publicar")->assertUnprocessable()->assertJsonValidationErrors('disciplina_ids');
        $this->postJson("/api/v1/provas/$id/gabarito/publicar")->assertUnprocessable()->assertJsonValidationErrors('disciplina_ids');
        $this->putJson("/api/v1/provas/$id",['status'=>'publicada'])->assertUnprocessable();
        $this->assertDatabaseHas('provas',['id'=>$id,'status'=>'rascunho']);
        $this->assertDatabaseHas('gabaritos',['id'=>$gab,'publicado_em'=>null]);
        $this->assertDatabaseHas('prova_curriculos',['prova_id'=>$id,'publicado_em'=>null]);
    }

    public function test_publicar_preserva_areas_anteriores_e_bloqueia_reclassificacao_apos_reabrir_rascunho(): void
    {
        [$m,$f]=$this->preparar();$id=$this->criar([$m]);
        $this->gabarito($id);
        $this->postJson('/api/v1/areas-conhecimento',['nome'=>'Área posterior','ativo'=>true,'disciplina_ids'=>[$m],'disciplina_versoes'=>DB::table('disciplinas')->pluck('versao','id')->all()])->assertCreated();
        $this->postJson("/api/v1/provas/$id/gabarito/publicar")->assertOk();
        $this->assertDatabaseCount('prova_area_vinculos',0);
        $this->assertNotNull(DB::table('prova_curriculos')->where('prova_id',$id)->value('publicado_em'));
        $this->putJson("/api/v1/provas/$id",['status'=>'rascunho'])->assertOk();
        $this->getJson("/api/v1/provas/$id")->assertOk()->assertJsonPath('data.organizacao_curricular.editavel',false);
        $this->putJson("/api/v1/provas/$id",['disciplina_ids'=>[$f],'turma_ids'=>[1,3],'curriculo_versao'=>1])->assertUnprocessable();
        $this->deleteJson("/api/v1/provas/$id")->assertConflict();
        $this->assertDatabaseHas('prova_disciplinas',['prova_id'=>$id,'disciplina_id'=>$m]);
    }

    public function test_ids_estrangeiros_e_campos_textuais_nao_substituem_identidade_e_flag_desligada_preserva_fluxo(): void
    {
        [$m,$f]=$this->preparar();
        $this->postJson('/api/v1/provas',$this->dados([$m])+['disciplina'=>'Identidade textual forjada'])->assertUnprocessable();
        $this->postJson('/api/v1/provas',$this->dados([999]))->assertUnprocessable();
        $this->postJson('/api/v1/provas',array_diff_key($this->dados([$m]),['disciplina_ids'=>1]))->assertUnprocessable()->assertJsonValidationErrors('disciplina_ids');
        $this->postJson('/api/v1/provas',$this->dados([$m],[2]))->assertNotFound();
        config(['disciplinas.ativo'=>false]);
        $this->getJson('/api/v1/provas/opcoes-disciplinas')->assertNotFound();
        $this->postJson('/api/v1/provas',array_diff_key($this->dados([$m]),['disciplina_ids'=>1])+['disciplina'=>'Legado explícito'])->assertCreated();
        $this->assertDatabaseCount('prova_curriculos',0);
    }

    public function test_publicacao_do_gabarito_nao_exige_capacidade_extra_de_editar_metadata_da_prova(): void
    {
        [$m,$f]=$this->preparar();$id=$this->criar([$m]);$this->gabarito($id);
        DB::table('vinculo_permissoes')->insert(['vinculo_id'=>1,'permissao'=>'prova.editar','efeito'=>'retirar']);
        $this->postJson("/api/v1/provas/$id/publicar")->assertForbidden();
        $this->postJson("/api/v1/provas/$id/gabarito/publicar")->assertOk();
        $this->assertDatabaseHas('provas',['id'=>$id,'status'=>'publicada']);
    }

    public function test_chave_composta_recusa_disciplina_estrangeira_no_banco(): void
    {
        [$m,$f]=$this->preparar();$id=$this->criar([$m]);
        DB::table('disciplinas')->insert(['id'=>90,'espaco_id'=>6,'nome'=>'Outra escola','nome_chave'=>'outra']);
        $this->expectException(\Illuminate\Database\QueryException::class);
        DB::table('prova_disciplinas')->insert(['espaco_id'=>5,'prova_id'=>$id,'disciplina_id'=>90,'nome'=>'Forjada']);
    }

    public function test_copia_cp1_continua_sem_turmas_e_configura_curriculo_antes_de_publicar(): void
    {
        [$m,$f]=$this->preparar();$this->entrarComo(2,3);$origem=$this->criar([$m]);$this->gabarito($origem);
        $antes=DB::table('gabarito_questoes')->orderBy('id')->get()->toJson();
        $id=$this->postJson("/api/v1/provas/$origem/copiar",['titulo'=>'Cópia independente','chave'=>(string)Str::uuid()])->assertCreated()->json('data.id');
        $this->assertDatabaseMissing('prova_turmas',['prova_id'=>$id]);
        $this->assertDatabaseMissing('prova_curriculos',['prova_id'=>$id]);
        $this->postJson("/api/v1/provas/$id/gabarito/publicar")->assertUnprocessable();
        $this->putJson("/api/v1/provas/$id",['disciplina_ids'=>[$m],'turma_ids'=>[1,3],'curriculo_versao'=>0])->assertOk();
        $this->postJson("/api/v1/provas/$id/gabarito/publicar")->assertOk();
        $this->assertSame($antes,DB::table('gabarito_questoes')->whereIn('gabarito_id',DB::table('gabaritos')->where('prova_id','!=',$id)->select('id'))->orderBy('id')->get()->toJson());
    }

    public function test_espaco_pessoal_mantem_disciplina_textual_sem_catalogo_oficial(): void
    {
        config(['espacos.pessoal'=>true]);
        DB::table('clientes')->insert(['id'=>9,'tipo'=>'professor','nome'=>'Pessoal fictício','modalidade'=>'interno','situacao'=>'ativo']);
        DB::table('espacos')->insert(['id'=>9,'cliente_id'=>9,'tipo'=>'pessoal','nome'=>'Meu espaço','situacao'=>'ativo','usuario_proprietario_id'=>3]);
        DB::table('usuario_vinculos')->insert(['id'=>9,'usuario_id'=>3,'espaco_id'=>9,'perfil'=>'professor_autonomo','situacao'=>'ativo','inicio_em'=>now()->subDay()]);
        DB::table('turmas')->insert(['id'=>9,'escola_id'=>null,'espaco_id'=>9,'espaco_tipo'=>'pessoal','nome'=>'Turma pessoal','serie'=>'1','turno'=>'tarde','ano_letivo'=>2026]);
        $this->entrarComo(3,9);
        $this->postJson('/api/v1/provas',['titulo'=>'Prova pessoal','disciplina'=>'Disciplina livre','serie'=>'1','num_questoes'=>1,'num_alternativas'=>4,'turma_ids'=>[9]])->assertCreated()->assertJsonPath('data.disciplina','Disciplina livre')->assertJsonPath('data.escola_id',null);
        $this->assertDatabaseCount('prova_curriculos',0);
        $this->getJson('/api/v1/provas/opcoes-disciplinas')->assertNotFound();
    }
}
