<?php
namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use App\Support\Permissao;
use Tests\TestCase;

class PainelTurmaIndicadoresTest extends TestCase
{
    use RefreshDatabase,\Tests\Support\CenarioDisciplinas;

    private function prova(int $id=1,?string $data='2026-11-03'): void
    {
        if($id!==1)DB::table('provas')->insert(['id'=>$id,'escola_id'=>1,'espaco_id'=>5,'criado_por'=>1,'titulo'=>'Avaliação fictícia '.$id,'disciplina'=>'Matemática','serie'=>'1','num_questoes'=>10,'num_alternativas'=>4]);
        DB::table('provas')->where('id',$id)->update(['status'=>'corrigida','data_aplicacao'=>$data,'nota_maxima'=>10,'perfil_criacao'=>'coordenador']);
        DB::table('prova_turmas')->insert(['prova_id'=>$id,'turma_id'=>1,'espaco_id'=>5]);
    }

    private function cartao(int $id,string $status='lido',?int $aluno=null,int $prova=1): void
    {
        if($aluno&&!DB::table('alunos')->where('id',$aluno)->exists())DB::table('alunos')->insert(['id'=>$aluno,'turma_id'=>1,'espaco_id'=>5,'nome'=>'Aluno fictício '.$aluno,'matricula'=>'F'.$aluno]);
        DB::table('cartoes')->insert(['id'=>$id,'prova_id'=>$prova,'aluno_id'=>$aluno,'espaco_id'=>5,'turma_aplicacao_id'=>1,'status'=>$status]);
    }

    private function nota(int $cartao,int $aluno,int $acertos,int $total,string $situacao='aprovado',int $prova=1): void
    {
        DB::table('notas')->insert(['cartao_id'=>$cartao,'prova_id'=>$prova,'aluno_id'=>$aluno,'turma_id'=>1,'espaco_id'=>5,'acertos'=>$acertos,'total_questoes'=>$total,'nota_final'=>$total?10*$acertos/$total:0,'status_aprovacao'=>$situacao]);
    }

    public function test_resultado_final_exclui_revisao_e_denominadores_invalidos_sem_recalcular_criterio(): void
    {
        $this->prova();$this->prova(3);$this->prova(4,null);
        $this->cartao(1,'lido',1);$this->nota(1,1,8,10);
        $this->cartao(2,'lido',2);$this->nota(2,2,8,20,'recuperacao');
        $this->cartao(3,'ambiguo',3);$this->nota(3,3,10,10);
        $this->cartao(4,'lido',1,3);$this->nota(4,1,0,0,'recuperacao',3);
        $this->getJson('/api/v1/turmas/1/painel')->assertOk()
            ->assertJsonPath('data.kpis.resultados',3)->assertJsonPath('data.kpis.resultados_com_percentual',2)
            ->assertJsonPath('data.kpis.acerto_pct',60)->assertJsonPath('data.kpis.alunos_com_resultado',2)
            ->assertJsonPath('data.kpis.avaliacoes_sem_resultado',1)->assertJsonPath('data.kpis.avaliacoes_sem_data',1)
            ->assertJsonPath('data.situacoes_resultados',['total'=>3,'dentro_criterio'=>1,'indicacao_recuperacao'=>2,'outros'=>0])
            ->assertJsonPath('data.acompanhamento.revisao',1);
        $this->getJson('/api/v1/turmas/1/painel?inicio=2026-11-01')->assertOk()->assertJsonPath('data.kpis.avaliacoes_sem_data',0);
    }

    public function test_operacoes_contam_cartao_vigente_uma_vez_e_nao_atribuem_arquivo_sem_turma(): void
    {
        $this->prova();$this->cartao(1,'pendente',1);$this->cartao(2,'pendente',2);
        $this->cartao(3,'ambiguo');$this->cartao(4,'pendente');
        DB::table('cartoes')->where('id',4)->update(['turma_aplicacao_id'=>null]);
        foreach([[1,'erro'],[1,'na_fila'],[2,'recusado']]as[$cartao,$status])DB::table('cartao_envios')->insert(['chave'=>(string)Str::uuid(),'usuario_id'=>1,'prova_id'=>1,'cartao_id'=>$cartao,'espaco_id'=>5,'status'=>$status]);
        $this->getJson('/api/v1/turmas/1/painel')->assertOk()->assertJsonPath('data.kpis.resultados',0)
            ->assertJsonPath('data.kpis.acerto_pct',null)->assertJsonPath('data.acompanhamento.total',3)
            ->assertJsonPath('data.acompanhamento.processamento',1)->assertJsonPath('data.acompanhamento.falhas',1)
            ->assertJsonPath('data.acompanhamento.revisao',1)->assertJsonPath('data.acompanhamento.nao_identificados',1)
            ->assertJsonPath('data.acompanhamento.base_esperada_confiavel',false);
    }

    public function test_historico_nao_depende_da_matricula_atual_e_operacao_respeita_retirada_de_permissao(): void
    {
        $this->prova();$this->cartao(1,'lido',1);$this->nota(1,1,9,10);
        DB::table('turmas')->insert(['id'=>3,'escola_id'=>1,'espaco_id'=>5,'nome'=>'Outra turma','serie'=>'1','turno'=>'tarde','ano_letivo'=>2026]);
        DB::table('alunos')->where('id',1)->update(['turma_id'=>3]);
        $this->getJson('/api/v1/turmas/1/painel')->assertOk()->assertJsonPath('data.kpis.alunos',0)->assertJsonPath('data.kpis.resultados',1)->assertJsonPath('data.acompanhamento.total',1);
        DB::table('vinculo_permissoes')->insert(['vinculo_id'=>1,'permissao'=>'correcao.acompanhar','efeito'=>'retirar']);Permissao::limparCache();
        $this->getJson('/api/v1/turmas/1/painel')->assertOk()->assertJsonPath('data.kpis.resultados',1)->assertJsonPath('data.acompanhamento',null)->assertJsonPath('data.avaliacoes.0.pendencias_revisao',null);
        $this->getJson('/api/v1/turmas/2/painel')->assertNotFound();
    }
}
