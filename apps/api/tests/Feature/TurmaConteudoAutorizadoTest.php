<?php
namespace Tests\Feature;

use App\Models\Usuario;
use App\Support\Permissao;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class TurmaConteudoAutorizadoTest extends TestCase
{
    use RefreshDatabase,\Tests\Support\CenarioDisciplinas;

    private function preparar(): void
    {
        DB::table('provas')->insert(['id'=>3,'escola_id'=>1,'espaco_id'=>5,'criado_por'=>2,'perfil_criacao'=>'professor',
            'titulo'=>'Prova autorizada','disciplina'=>'Matemática','serie'=>'1','num_questoes'=>10,'num_alternativas'=>4,'status'=>'corrigida','data_aplicacao'=>'2026-11-03']);
        DB::table('prova_turmas')->insert(['prova_id'=>3,'turma_id'=>1,'espaco_id'=>5]);
        DB::table('alunos')->insert(['id'=>1,'turma_id'=>1,'espaco_id'=>5,'nome'=>'Estudante fictício','matricula'=>'P1-1']);
        DB::table('cartoes')->insert(['id'=>1,'prova_id'=>3,'aluno_id'=>1,'espaco_id'=>5,'status'=>'lido']);
        DB::table('notas')->insert(['cartao_id'=>1,'prova_id'=>3,'aluno_id'=>1,'turma_id'=>1,'espaco_id'=>5,
            'acertos'=>8,'total_questoes'=>10,'nota_final'=>8,'status_aprovacao'=>'aprovado']);
        app('auth')->forgetGuards();Sanctum::actingAs(Usuario::findOrFail(2)->noVinculo(3));
    }

    private function somente(array $chaves): void
    {
        DB::table('vinculo_permissoes')->where('vinculo_id',3)->delete();
        $u=Usuario::findOrFail(2)->noVinculo(3);
        foreach(array_diff(Permissao::efetivas($u),$chaves) as $p)
            DB::table('vinculo_permissoes')->insert(['vinculo_id'=>3,'permissao'=>$p,'efeito'=>'retirar']);
    }

    public function test_turma_ver_so_nao_envia_resultados_provas_nomes_ou_consulta_notas(): void
    {
        $this->preparar();$this->somente(['turma.ver']);DB::enableQueryLog();DB::flushQueryLog();
        $this->getJson('/api/v1/turmas')->assertOk()->assertJsonMissingPath('data.turmas.0.media_turma')
            ->assertJsonPath('data.turmas.0.total_alunos',1);
        $this->getJson('/api/v1/turmas/1')->assertOk()->assertJsonPath('data.ativo',true)
            ->assertJsonPath('data.total_alunos',1)->assertJsonCount(0,'data.alunos')->assertJsonCount(0,'data.provas')
            ->assertJsonMissingPath('data.media_turma')->assertJsonMissingPath('data.total_provas')->assertDontSee('Estudante fictício');
        $this->assertSame([],array_values(array_filter(DB::getQueryLog(),fn($q)=>preg_match('/\bnotas\b/i',$q['query']))));
        $this->getJson('/api/v1/turmas/2')->assertNotFound();
    }

    public function test_prova_ver_so_entrega_metadados_e_status_sem_resultados(): void
    {
        $this->preparar();$this->somente(['turma.ver','prova.ver']);
        $this->getJson('/api/v1/turmas')->assertOk()->assertJsonMissingPath('data.turmas.0.media_turma');
        $this->getJson('/api/v1/turmas/1')->assertOk()->assertJsonCount(0,'data.alunos')->assertJsonCount(1,'data.provas')
            ->assertJsonPath('data.provas.0.status','corrigida')->assertJsonPath('data.total_provas',1)
            ->assertJsonMissingPath('data.provas.0.media')->assertJsonMissingPath('data.media_turma');
    }

    public function test_relatorio_turma_so_entrega_agregado_sem_identificar_estudantes_ou_provas(): void
    {
        $this->preparar();$this->somente(['turma.ver','relatorio.turma']);
        $this->getJson('/api/v1/turmas')->assertOk()->assertJsonPath('data.turmas.0.media_turma',8);
        $this->getJson('/api/v1/turmas/1')->assertOk()->assertJsonPath('data.media_turma',8)
            ->assertJsonCount(0,'data.alunos')->assertJsonCount(0,'data.provas')->assertJsonMissingPath('data.total_provas')
            ->assertDontSee('Estudante fictício')->assertDontSee('Prova autorizada');
    }

    public function test_resultado_e_aluno_sem_relatorio_nao_autorizam_consolidado_da_turma(): void
    {
        $this->preparar();$this->somente(['turma.ver','aluno.ver','resultado.ver']);
        $this->getJson('/api/v1/turmas/1')->assertOk()->assertJsonPath('data.alunos.0.nome','Estudante fictício')
            ->assertJsonMissingPath('data.alunos.0.media_geral')->assertJsonMissingPath('data.alunos.0.total_provas')
            ->assertJsonMissingPath('data.media_turma')->assertJsonMissingPath('data.total_provas')->assertJsonCount(0,'data.provas');
    }

    public function test_agregado_com_nome_sem_resultado_ver_nao_envia_metricas_individuais(): void
    {
        $this->preparar();$this->somente(['turma.ver','aluno.ver','relatorio.turma']);
        $this->getJson('/api/v1/turmas/1')->assertOk()->assertJsonPath('data.media_turma',8)->assertJsonPath('data.alunos.0.nome','Estudante fictício')
            ->assertJsonMissingPath('data.alunos.0.media_geral')->assertJsonMissingPath('data.alunos.0.total_provas');
    }

    public function test_permissoes_completas_preservam_shape_e_retirada_na_mesma_identidade_e_imediata(): void
    {
        $this->preparar();
        $this->getJson('/api/v1/turmas')->assertOk()->assertJsonPath('data.turmas.0.media_turma',8);
        $this->getJson('/api/v1/turmas/1')->assertOk()->assertJsonPath('data.media_turma',8)->assertJsonPath('data.total_provas',1)
            ->assertJsonPath('data.alunos.0.media_geral',8)->assertJsonPath('data.alunos.0.total_provas',1)
            ->assertJsonPath('data.provas.0.media',8)->assertJsonPath('data.provas.0.status','corrigida')
            ->assertJsonPath('data.ativo',true)->assertJsonMissingPath('data.alunos.0.foto_path');
        $this->somente(['turma.ver','aluno.ver','prova.ver']);
        $this->getJson('/api/v1/turmas')->assertOk()->assertJsonMissingPath('data.turmas.0.media_turma');
        $this->getJson('/api/v1/turmas/1')->assertOk()->assertJsonMissingPath('data.media_turma')
            ->assertJsonMissingPath('data.alunos.0.total_provas')->assertJsonMissingPath('data.provas.0.media');
        DB::table('usuario_vinculos')->where('id',3)->update(['situacao'=>'suspenso']);
        $this->getJson('/api/v1/turmas/1')->assertForbidden();
    }

    public function test_administrativos_nao_recebem_pedagogia_mesmo_com_extras_injetados(): void
    {
        $this->preparar();
        foreach(['secretario_escolar','auxiliar_secretaria']as$perfil){
            DB::table('usuario_vinculos')->where('id',3)->update(['perfil'=>$perfil]);
            foreach(['relatorio.turma','resultado.ver','prova.ver']as$p)DB::table('vinculo_permissoes')->updateOrInsert(['vinculo_id'=>3,'permissao'=>$p],['efeito'=>'conceder']);
            app('auth')->forgetGuards();Sanctum::actingAs(Usuario::findOrFail(2)->noVinculo(3));
            $this->getJson('/api/v1/turmas')->assertOk()->assertJsonMissingPath('data.turmas.0.media_turma');
            $this->getJson('/api/v1/turmas/1')->assertOk()->assertJsonPath('data.alunos.0.nome','Estudante fictício')
                ->assertJsonMissingPath('data.alunos.0.media_geral')->assertJsonMissingPath('data.alunos.0.total_provas')
                ->assertJsonMissingPath('data.media_turma')->assertJsonMissingPath('data.total_provas')->assertJsonMissingPath('data.provas');
            $this->getJson('/api/v1/turmas/1/painel')->assertForbidden();
            $this->getJson('/api/v1/turmas/1/painel/pdf')->assertForbidden();
        }
    }
}
