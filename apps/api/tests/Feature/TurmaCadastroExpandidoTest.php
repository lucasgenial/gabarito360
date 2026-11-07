<?php
namespace Tests\Feature;

use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class TurmaCadastroExpandidoTest extends TestCase
{
    use \Tests\Support\BancoCurriculoDescartavel, \Tests\Support\CenarioDisciplinas;

    public function test_campos_opcionais_persistem_sem_mudar_escola_ou_dados_anteriores(): void
    {
        $antes=DB::table('provas')->get()->toJson();
        $campos=['sala'=>'Laboratório 2','capacidade'=>35,'etapa_ensino'=>'ensino_medio','observacoes'=>'Organização de teste'];
        $this->putJson('/api/v1/turmas/1',$campos)->assertOk()->assertJsonPath('data.capacidade',35)->assertJsonPath('data.sala','Laboratório 2');
        $this->putJson('/api/v1/turmas/1',['nome'=>'Nova identificação'])->assertOk()->assertJsonPath('data.observacoes','Organização de teste');
        $this->assertSame($antes,DB::table('provas')->get()->toJson());
        $this->putJson('/api/v1/turmas/1',array_fill_keys(array_keys($campos),null))->assertOk()->assertJsonPath('data.capacidade',null)->assertJsonPath('data.etapa_ensino',null);
        $this->assertDatabaseHas('turmas',['id'=>1,'escola_id'=>1,'espaco_id'=>5]);
    }

    public function test_limites_e_escopo_recusam_alteracao_sem_persistencia_parcial(): void
    {
        $this->putJson('/api/v1/turmas/1',['nome'=>'Não gravar','capacidade'=>0,'sala'=>str_repeat('x',51),'observacoes'=>str_repeat('x',1001),'etapa_ensino'=>'inventada'])
            ->assertUnprocessable()->assertJsonValidationErrors(['capacidade','sala','observacoes','etapa_ensino']);
        $this->assertDatabaseHas('turmas',['id'=>1,'nome'=>'1A','capacidade'=>null]);
        $this->putJson('/api/v1/turmas/2',['sala'=>'Outra escola'])->assertNotFound();
        $this->putJson('/api/v1/turmas/1',['capacidade'=>1])->assertOk();
        $this->assertDatabaseHas('turmas',['id'=>1,'capacidade'=>1]);
    }
}
