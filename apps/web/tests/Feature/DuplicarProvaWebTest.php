<?php
namespace Tests\Feature;

use Illuminate\Support\Facades\Http;
use Tests\TestCase;
use Tests\Support\NavegacaoHttp;

class DuplicarProvaWebTest extends TestCase
{
    use NavegacaoHttp;
    use \Tests\Support\AcessoAtivoLegado;
    private function preparar(bool $elegivel=true): void
    {
        config(['services.api.base_url'=>'http://api.test/api']);
        config(['release.fase'=>'INICIAL-V1-T5','release.sha'=>'a90fbf51-teste-visual']);
        $this->withSession(['auth_token'=>'token-ficticio','auth_nome'=>'Professor','auth_perfil'=>'professor','auth_permissoes'=>['prova.ver','prova.criar','prova.duplicar_propria','gabarito.ver']]);
        $this->selecionarAcessoLegado('professor', ['prova.ver','prova.criar','prova.duplicar_propria','gabarito.ver'], 'token-ficticio');
        Http::preventStrayRequests();
        $p=['id'=>1,'titulo'=>'Matemática <script>','disciplina'=>'Matemática','serie'=>'1º ano','pode_copiar'=>$elegivel,'status'=>'publicada','num_questoes'=>10,'data_aplicacao'=>null,'turmas'=>[],'progresso'=>['tipo'=>'nenhum']];
        $this->apiLegada(['*/v1/provas/1'=>Http::response(['data'=>$p]),'*/v1/provas'=>Http::response(['data'=>['provas'=>[$p],'kpis'=>[]]])]);
    }

    public function test_formulario_explica_copia_escapa_origem_e_preserva_intencao(): void
    {
        $this->preparar();
        $r=$this->consultarTela('/app/provas')->assertOk()->assertSee('Criar cópia')->assertSee('não serão copiados')->assertSee('Matemática &lt;script&gt;',false)
            ->assertSee('aria-label="Versão do software"',false)->assertSee('0.1.0')->assertDontSee('Versão de testes: INICIAL-V1-T5 · a90fbf51');
        $this->assertStringContainsString('name="chave"',$r->getContent());
        $r->assertSee('aria-haspopup="dialog"',false)->assertSee('id="cp1-modal"',false)->assertSee('Nome da nova prova')->assertDontSee('Ver o que será copiado');
        if (getenv('G360_CP1_PREVIEW_DIR')) {
            file_put_contents(getenv('G360_CP1_PREVIEW_DIR').'/provas.html',$r->getContent());
        }
    }

    public function test_envio_encaminha_apenas_titulo_chave_e_redireciona_para_nova_prova(): void
    {
        $this->preparar();
        $this->apiLegada(['*/v1/provas/1/copiar'=>Http::response(['data'=>['id'=>3]],201)]);
        $chave='aaaaaaaa-aaaa-4aaa-8aaa-aaaaaaaaaaaa';
        $this->alterarRecurso('POST', '/app/provas/1/copiar',['titulo'=>'Versão B','chave'=>$chave,'turma_ids'=>[9]])->assertRetornoTela('/app/provas/3')->assertSessionHas('sucesso');
        Http::assertSent(fn($r)=>$r->method()==='POST' && $r['titulo']==='Versão B' && $r['chave']===$chave && !isset($r['turma_ids']));
    }

    public function test_falha_preserva_dados_e_nao_exibe_sucesso(): void
    {
        $this->preparar();
        $this->apiLegada(['*/v1/provas/1/copiar'=>Http::response(['message'=>'Solicitação conflitante'],409)]);
        $this->alterarRecurso('POST', '/app/provas/1/copiar',['titulo'=>'Versão B','chave'=>'aaaaaaaa-aaaa-4aaa-8aaa-aaaaaaaaaaaa'])
            ->assertRetornoTela('/app/provas')->assertSessionHas('erro','Solicitação conflitante')->assertSessionMissing('sucesso')->assertSessionHasInput('titulo','Versão B')->assertSessionHasInput('origem_id',1);
    }

    public function test_origem_nao_elegivel_nao_abre_formulario(): void
    {
        $this->preparar(false);
        $this->consultarTela('/app/provas')->assertOk()->assertDontSee('data-origem="1"',false);
        $this->get('/app/provas/1/copiar')->assertRedirect('/app/painel');
        Http::assertNotSent(fn($r)=>$r->method()!=='GET');
    }

    public function test_nova_avaliacao_mostra_formato_suportado_sem_prometer_editor(): void
    {
        $this->preparar();
        $this->apiLegada(['*/v1/turmas*'=>Http::response(['data'=>['turmas'=>[]]]),'*/v1/escolas*'=>Http::response(['data'=>['escolas'=>[]]])]);
        $r=$this->consultarTela('/app/provas/nova')->assertOk()->assertSee('Formato da avaliação')->assertSee('Gabarito e cartão-resposta')->assertSee('name="formato_avaliacao"',false);
        if (getenv('G360_CP1_PREVIEW_DIR')) file_put_contents(getenv('G360_CP1_PREVIEW_DIR').'/nova.html',$r->getContent());
    }
}
