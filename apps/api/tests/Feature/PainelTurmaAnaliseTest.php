<?php
namespace Tests\Feature;

use App\Models\Usuario;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class PainelTurmaAnaliseTest extends TestCase
{
    use RefreshDatabase,\Tests\Support\CenarioDisciplinas;

    private function prova(int $id=1):void
    {
        if($id!==1)DB::table('provas')->insert(['id'=>$id,'escola_id'=>1,'espaco_id'=>5,'criado_por'=>1,'titulo'=>'Prova '.$id,'disciplina'=>'Matemática','serie'=>'1','num_questoes'=>10,'num_alternativas'=>4]);
        DB::table('provas')->where('id',$id)->update(['status'=>'corrigida','data_aplicacao'=>'2026-11-03','nota_maxima'=>10,'perfil_criacao'=>'coordenador']);
        DB::table('prova_turmas')->insert(['prova_id'=>$id,'turma_id'=>1,'espaco_id'=>5]);
    }

    private function resultado(int $id,int $acertos,int $total=10,string $status='lido'):void
    {
        DB::table('alunos')->insert(['id'=>$id,'turma_id'=>1,'espaco_id'=>5,'nome'=>'Fictício '.$id,'matricula'=>'F'.$id]);
        DB::table('cartoes')->insert(['id'=>$id,'prova_id'=>1,'aluno_id'=>$id,'espaco_id'=>5,'status'=>$status]);
        DB::table('notas')->insert(['cartao_id'=>$id,'prova_id'=>1,'aluno_id'=>$id,'turma_id'=>1,'espaco_id'=>5,'acertos'=>$acertos,'total_questoes'=>$total,'nota_final'=>$total?10*$acertos/$total:0,'status_aprovacao'=>'aprovado']);
    }

    public function test_distribuicao_mediana_dispersao_e_selecao_de_questoes_nao_filtra_resultados():void
    {
        $this->prova();$this->prova(3);
        foreach([10=>'Matemática',11=>'Física']as$id=>$nome)DB::table('disciplinas')->insert(['id'=>$id,'espaco_id'=>5,'nome'=>$nome,'nome_chave'=>$nome,'ativo'=>true]);
        DB::table('prova_curriculos')->insert(['prova_id'=>1,'espaco_id'=>5,'ator_vinculo_id'=>1]);
        foreach([10,11]as$id)DB::table('prova_disciplinas')->insert(['prova_id'=>1,'espaco_id'=>5,'disciplina_id'=>$id,'nome'=>'Disciplina '.$id]);
        foreach([0,2,8,10]as$i=>$acertos)$this->resultado($i+1,$acertos);
        $this->resultado(5,0,0);$this->resultado(6,10,10,'ambiguo');
        $r=$this->getJson('/api/v1/turmas/1/painel?analise_prova_id=3')->assertOk();
        $r->assertJsonPath('data.kpis.resultados',5)->assertJsonPath('data.analise.resumo',['n'=>4,'media_pct'=>50,'mediana_pct'=>50,'min_pct'=>0,'max_pct'=>100,'desvio_pct'=>41.2])
            ->assertJsonPath('data.analise.distribuicao_acertos.total',4)->assertJsonPath('data.analise.distribuicao_acertos.percentuais_excluidos',1)
            ->assertJsonPath('data.analise.distribuicao_acertos.faixas.0.total',1)->assertJsonPath('data.analise.distribuicao_acertos.faixas.2.total',1)
            ->assertJsonPath('data.analise.distribuicao_acertos.faixas.8.total',1)->assertJsonPath('data.analise.distribuicao_acertos.faixas.9.total',1)
            ->assertJsonPath('data.analise.por_avaliacao.0.resultados_total',5)->assertJsonPath('data.analise.por_avaliacao.0.percentuais_excluidos',1)
            ->assertJsonPath('data.analise.por_avaliacao.0.abrangencia','avaliacao_completa')
            ->assertJsonPath('data.analise.por_avaliacao.1.n',0)->assertJsonPath('data.analise.por_avaliacao.1.mediana_pct',null)
            ->assertJsonPath('data.analise.por_avaliacao.1.resultados_total',0)->assertJsonPath('data.analise.por_avaliacao.1.percentuais_excluidos',0)
            ->assertJsonPath('data.analise.por_avaliacao.1.abrangencia','avaliacao_completa')
            ->assertJsonPath('data.analise.questoes.avaliacao_id',3)->assertJsonCount(0,'data.analise.questoes.items');
        $this->getJson('/api/v1/turmas/1/painel?analise_prova_id=2')->assertUnprocessable();
        $this->getJson('/api/v1/turmas/1/painel?analise_prova_id=0')->assertUnprocessable();
        foreach([10,11]as$disciplina)$this->getJson('/api/v1/turmas/1/painel?disciplina_id='.$disciplina)->assertOk()
            ->assertJsonPath('data.analise.resumo.n',4)->assertJsonCount(1,'data.analise.por_avaliacao')
            ->assertJsonPath('data.analise.por_avaliacao.0.resultados_total',5)->assertJsonPath('data.analise.por_avaliacao.0.percentuais_excluidos',1)
            ->assertJsonPath('data.analise.por_avaliacao.0.abrangencia','avaliacao_completa');
    }

    public function test_mediana_impar_amostra_unica_e_vazia_nao_inventam_percentual():void
    {
        $this->prova();
        $this->getJson('/api/v1/turmas/1/painel')->assertOk()->assertJsonPath('data.analise.resumo.n',0)->assertJsonPath('data.analise.resumo.mediana_pct',null);
        $this->resultado(1,4);
        $this->getJson('/api/v1/turmas/1/painel')->assertOk()->assertJsonPath('data.analise.resumo.mediana_pct',40)->assertJsonPath('data.analise.resumo.desvio_pct',0);
        $this->resultado(2,0);$this->resultado(3,10);
        $this->getJson('/api/v1/turmas/1/painel')->assertOk()->assertJsonPath('data.analise.resumo.mediana_pct',40)->assertJsonPath('data.analise.por_avaliacao.0.mediana_pct',40);
    }

    public function test_questoes_respeitam_lingua_anulada_branco_sem_registro_e_turma():void
    {
        $this->prova();DB::table('provas')->where('id',1)->update(['opcao_lingua'=>true]);
        $this->resultado(1,1);$this->resultado(2,1);
        DB::table('turmas')->insert(['id'=>3,'escola_id'=>1,'espaco_id'=>5,'nome'=>'Outra turma','serie'=>'1','turno'=>'tarde','ano_letivo'=>2026]);
        DB::table('prova_turmas')->insert(['prova_id'=>1,'turma_id'=>3,'espaco_id'=>5]);
        $this->resultado(3,10);
        DB::table('alunos')->where('id',3)->update(['turma_id'=>3]);
        DB::table('notas')->where('aluno_id',3)->update(['turma_id'=>3]);
        DB::table('cartoes')->where('id',1)->update(['lingua'=>'ING']);DB::table('cartoes')->where('id',2)->update(['lingua'=>'ESP']);
        DB::table('gabaritos')->insert(['id'=>1,'prova_id'=>1,'espaco_id'=>5]);
        foreach([1,2,3]as$q)DB::table('gabarito_questoes')->insert(['gabarito_id'=>1,'espaco_id'=>5,'numero_questao'=>$q,'alternativa'=>'A','alternativa_espanhol'=>'C','anulada'=>$q===2]);
        DB::table('cartao_respostas')->insert([
            ['cartao_id'=>1,'espaco_id'=>5,'numero_questao'=>1,'alternativa'=>'A'],
            ['cartao_id'=>2,'espaco_id'=>5,'numero_questao'=>1,'alternativa'=>'C'],
            ['cartao_id'=>2,'espaco_id'=>5,'numero_questao'=>3,'alternativa'=>null],
        ]);
        $r=$this->getJson('/api/v1/turmas/1/painel')->assertOk();
        $r->assertJsonPath('data.analise.questoes.items.0.acertos',2)->assertJsonPath('data.analise.questoes.items.0.base',2)->assertJsonPath('data.analise.questoes.items.0.pct',100)
            ->assertJsonCount(2,'data.analise.questoes.items.0.por_lingua')->assertJsonPath('data.analise.questoes.items.1.anulada',true)->assertJsonPath('data.analise.questoes.items.1.pct',null)
            ->assertJsonPath('data.analise.questoes.items.2.sem_registro',1)->assertJsonPath('data.analise.questoes.items.2.brancas',1);
        DB::table('provas')->where('id',1)->update(['opcao_lingua'=>false]);
        $this->getJson('/api/v1/turmas/1/painel')->assertOk()->assertJsonPath('data.analise.questoes.items.0.pct',50)
            ->assertJsonCount(1,'data.analise.questoes.items.0.por_lingua')->assertJsonPath('data.analise.questoes.items.0.por_lingua.0.lingua','UNICA');
        DB::table('vinculo_permissoes')->insert(['vinculo_id'=>1,'permissao'=>'relatorio.prova','efeito'=>'retirar']);
        $this->getJson('/api/v1/turmas/1/painel')->assertOk()->assertJsonPath('data.analise.questoes.disponivel',false)->assertJsonCount(0,'data.analise.questoes.items');
    }

    public function test_professor_nao_recebe_outra_autoria_nem_questoes_por_id_fora_do_escopo():void
    {
        $this->prova();DB::table('provas')->where('id',1)->update(['criado_por'=>3,'perfil_criacao'=>'professor','vinculo_criacao_id'=>5]);
        $this->resultado(1,8);
        app('auth')->forgetGuards();Sanctum::actingAs(Usuario::findOrFail(2)->noVinculo(3));
        $this->getJson('/api/v1/turmas/1/painel')->assertOk()->assertJsonPath('data.analise.resumo.n',0)->assertJsonCount(0,'data.analise.questoes.opcoes');
        $this->getJson('/api/v1/turmas/1/painel?analise_prova_id=1')->assertUnprocessable();
        $this->getJson('/api/v1/turmas/2/painel')->assertNotFound();
    }
    public function test_alunos_separam_criterio_atencao_e_destaques_com_empates_sem_inventar_diagnostico():void
    {
        $this->prova();foreach([10,9,9,8,7,7,1]as$i=>$n)$this->resultado($i+1,$n);
        DB::table('notas')->whereIn('aluno_id',[5,6,7])->update(['status_aprovacao'=>'recuperacao']);
        $r=$this->getJson('/api/v1/turmas/1/painel')->assertOk();
        $r->assertJsonPath('data.analise.alunos.disponivel',true)->assertJsonPath('data.analise.alunos.atencao.total',3)
            ->assertJsonPath('data.analise.alunos.dentro_criterio.total',4)->assertJsonCount(5,'data.analise.alunos.destaques.items')
            ->assertJsonPath('data.analise.alunos.destaques.items.0.posicao',1)->assertJsonPath('data.analise.alunos.destaques.items.1.posicao',2)
            ->assertJsonPath('data.analise.alunos.destaques.items.2.posicao',2)->assertJsonPath('data.analise.alunos.destaques.items.3.posicao',4)
            ->assertJsonPath('data.analise.alunos.destaques.empate_no_corte',true);
    }

}
