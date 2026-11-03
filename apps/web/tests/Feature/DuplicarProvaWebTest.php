<?php
namespace Tests\Feature;

use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class DuplicarProvaWebTest extends TestCase
{
    private function preparar(bool $elegivel=true): void
    {
        config(['services.api.base_url'=>'http://api.test/api']);
        $this->withSession(['auth_token'=>'token-ficticio','auth_nome'=>'Professor','auth_perfil'=>'professor','auth_permissoes'=>['prova.ver','prova.criar','prova.duplicar_propria','gabarito.ver']]);
        Http::preventStrayRequests();
        Http::fake(['*/v1/provas/1'=>Http::response(['data'=>['id'=>1,'titulo'=>'Matemática <script>','disciplina'=>'Matemática','serie'=>'1º ano','pode_copiar'=>$elegivel]])]);
    }

    public function test_formulario_explica_copia_escapa_origem_e_preserva_intencao(): void
    {
        $this->preparar();
        $r=$this->get('/provas/1/copiar')->assertOk()->assertSee('Criar cópia em rascunho')->assertSee('não serão copiados')->assertSee('Matemática &lt;script&gt;',false);
        $this->assertStringContainsString('name="chave"',$r->getContent());
        if (getenv('G360_CP1_PREVIEW_DIR')) {
            file_put_contents(getenv('G360_CP1_PREVIEW_DIR').'/copiar.html',$r->getContent());
        }
    }

    public function test_envio_encaminha_apenas_titulo_chave_e_redireciona_para_nova_prova(): void
    {
        $this->preparar();
        Http::fake(['*/v1/provas/1/copiar'=>Http::response(['data'=>['id'=>3]],201)]);
        $chave='aaaaaaaa-aaaa-4aaa-8aaa-aaaaaaaaaaaa';
        $this->post('/provas/1/copiar',['titulo'=>'Versão B','chave'=>$chave,'turma_ids'=>[9]])->assertRedirect('/provas/3')->assertSessionHas('sucesso');
        Http::assertSent(fn($r)=>$r->method()==='POST' && $r['titulo']==='Versão B' && $r['chave']===$chave && !isset($r['turma_ids']));
    }

    public function test_falha_preserva_dados_e_nao_exibe_sucesso(): void
    {
        $this->preparar();
        Http::fake(['*/v1/provas/1/copiar'=>Http::response(['message'=>'Solicitação conflitante'],409)]);
        $this->from('/provas/1/copiar')->post('/provas/1/copiar',['titulo'=>'Versão B','chave'=>'aaaaaaaa-aaaa-4aaa-8aaa-aaaaaaaaaaaa'])
            ->assertRedirect('/provas/1/copiar')->assertSessionHas('erro','Solicitação conflitante')->assertSessionMissing('sucesso')->assertSessionHasInput('titulo','Versão B');
    }

    public function test_origem_nao_elegivel_nao_abre_formulario(): void
    {
        $this->preparar(false);
        $this->get('/provas/1/copiar')->assertNotFound();
    }
}
