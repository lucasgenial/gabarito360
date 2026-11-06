<?php
namespace Tests\Feature;

use App\Support\ContextoWeb;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Tests\Support\{ContextoSelecionado,NavegacaoHttp};
use Tests\TestCase;

class TurmaOrganizacaoWebTest extends TestCase
{
    use ContextoSelecionado,NavegacaoHttp;
    private array $me;
    private string $falha='';

    protected function preparar(string $perfil='coordenador'): void
    {
        config(['services.api.base_url'=>'http://api.test/api','disciplinas.ativo'=>true]);
        Http::preventStrayRequests();
        $this->withSession(['auth_token'=>'identidade-ficticia','auth_vinculos'=>true]);
        $this->me=['token'=>'contexto-ficticio','tipo_contexto'=>'vinculo','vinculo_id'=>7,'perfil'=>$perfil,
            'permissoes'=>$perfil==='coordenador'?['turma.ver','turma.gerir','turma.vincular_professor','disciplina.gerir','aluno.gerir']:['turma.ver'],
            'espaco'=>['id'=>5,'tipo'=>'escola','nome'=>'Escola fictícia']];
        $this->selecionarContextoFixture($this->me);
        Http::fake(function($r){
            $p=parse_url($r->url(),PHP_URL_PATH);
            if($p===$this->falha)throw new ConnectionException('diagnostico-privado');
            $dados=match($p){
                '/api/v1/auth/me'=>$this->me,
                '/api/v1/auth/vinculos'=>[],
                '/api/v1/turmas/14','/api/v1/turmas/15'=>['id'=>(int)basename($p),'nome'=>str_ends_with($p,'14')?'Turma A':'Turma B','serie'=>'3ª série','turno'=>'tarde','escola_id'=>5,'ano_letivo'=>2026,'ativo'=>true,'media_turma'=>null,'total_alunos'=>0,'total_provas'=>0,'alunos'=>[],'provas'=>[]],
                '/api/v1/turmas/14/disciplinas','/api/v1/turmas/15/disciplinas'=>['componentes'=>[],'gerir'=>$this->me['perfil']==='coordenador','atribuir'=>$this->me['perfil']==='coordenador'],
                '/api/v1/disciplinas'=>['disciplinas'=>[['id'=>31,'nome'=>'Matemática <teste>','ativo'=>true]],'gerir'=>true],
                default=>['professores'=>[],'usuarios'=>[],'escolas'=>[]],
            };
            if($r->method()==='POST')$dados=['id'=>9];
            return Http::response(['data'=>$dados,'meta'=>['pagina'=>2,'paginas'=>3,'total'=>45]],$r->method()==='POST'?201:200);
        });
    }

    public function test_turma_tem_paineis_distintos_e_catalogo_paginado_sem_alterar_api(): void
    {
        $this->preparar();
        $r=$this->consultarTela('/app/turmas/14?secao=disciplinas&catalogo_busca=mat&catalogo_pagina=2')->assertOk()
            ->assertSee('data-secao-inicial="disciplinas"',false)->assertSee('Disciplina do catálogo da escola')
            ->assertSee('Página 2 de 3')->assertSee('Matemática &lt;teste&gt;',false)->assertDontSee('Matemática <teste>',false)
            ->assertSee('Adicionar à turma')->assertSee('Conceder acesso')->assertSee('Saiba mais sobre disciplinas');
        foreach(['visao-geral','alunos','disciplinas','provas','acesso']as$secao)$r->assertSee('data-turma-painel="'.$secao.'"',false);
        Http::assertSent(fn($r)=>parse_url($r->url(),PHP_URL_PATH)==='/api/v1/disciplinas' && $r['busca']==='mat' && (int)$r['page']===2 && (int)$r['ativo']===1 && !$r->offsetExists('secao'));
        Http::assertNotSent(fn($r)=>$r->method()!=='GET');
        $r->assertDestinoRecurso('turmas.show',['id'=>14],['secao'=>'disciplinas','catalogo_pagina'=>3]);
    }

    public function test_leitores_nao_consultam_catalogo_de_gestao_ou_lista_global_de_professores(): void
    {
        foreach(['professor','dir_escolar','vice_dir_escolar']as$perfil){
            $this->preparar($perfil);
            $this->consultarTela('/app/turmas/14')->assertOk()->assertSee('data-turma-painel="disciplinas"',false)
                ->assertDontSee('name="disciplina_id"',false)->assertDontSee('id="professor-selecionado"',false)->assertDontSee('id="modal-editar"',false);
            Http::assertNotSent(fn($r)=>in_array(parse_url($r->url(),PHP_URL_PATH),['/api/v1/usuarios','/api/v1/escolas','/api/v1/disciplinas'],true));
            $this->alterarRecurso('POST','/app/turmas/14/disciplinas',['disciplina_id'=>31])->assertForbidden();
            Http::assertNotSent(fn($r)=>$r->method()!=='GET');
        }
    }

    public function test_falha_catalogo_ou_acesso_nao_parece_lista_vazia_nem_destroi_turma(): void
    {
        $this->preparar();$this->falha='/api/v1/disciplinas';
        $this->consultarTela('/app/turmas/14?secao=disciplinas')->assertOk()->assertSee('Não foi possível consultar o catálogo')
            ->assertDontSee('Nenhuma disciplina ativa disponível')->assertDontSee('diagnostico-privado')->assertSee('Turma A');
        $this->falha='/api/v1/turmas/14/professores';
        $this->consultarTela('/app/turmas/14?secao=acesso')->assertOk()->assertSee('Não foi possível consultar os acessos')
            ->assertDontSee('id="professor-selecionado"',false)->assertDontSee('Nenhum professor com acesso direto');
    }

    public function test_adicionar_disciplina_nao_concede_acesso_nem_indica_ou_atribui_professor(): void
    {
        $this->preparar();
        $this->alterarRecurso('POST','/app/turmas/14/disciplinas',['disciplina_id'=>31])
            ->assertRetornoTela('/app/turmas/14/disciplinas/9');
        Http::assertSent(fn($r)=>$r->method()==='POST' && parse_url($r->url(),PHP_URL_PATH)==='/api/v1/turmas/14/disciplinas' && $r->data()===['disciplina_id'=>31]);
        Http::assertNotSent(fn($r)=>$r->method()!=='GET' && parse_url($r->url(),PHP_URL_PATH)!=='/api/v1/turmas/14/disciplinas');
    }

    public function test_erro_validacao_volta_para_disciplinas_e_acesso_volta_para_sua_secao(): void
    {
        $this->preparar();
        $this->alterarRecurso('POST','/app/turmas/14/disciplinas',['disciplina_id'=>'invalido'])
            ->assertRetornoTela('/app/turmas/14?secao=disciplinas')->assertSessionHasErrors('disciplina_id');
        $this->alterarRecurso('POST','/app/turmas/14/professores',['usuario_id'=>2,'vinculo_id'=>9])
            ->assertRetornoTela('/app/turmas/14?secao=acesso');
        Http::assertNotSent(fn($r)=>$r->method()!=='GET' && parse_url($r->url(),PHP_URL_PATH)!=='/api/v1/turmas/14/professores');
    }

    public function test_retorno_da_atribuicao_pertence_a_entrada_da_turma_e_nao_vaza_para_api(): void
    {
        $this->preparar();
        $rascunho=['retorno_turma'=>14,'retorno_componente'=>9,'rascunho_inicio'=>'2027-02-01','rascunho_papel'=>'substituicao','rascunho_chave'=>'11111111-1111-4111-8111-111111111111'];
        $a=\App\Support\NavegacaoWeb::destino('turmas.show',['id'=>14]+$rascunho);
        $b=\App\Support\NavegacaoWeb::destino('turmas.show',['id'=>15,'retorno_turma'=>15,'retorno_componente'=>8]);
        $this->alterarRecurso('POST','/app/turmas/14/professores',['usuario_id'=>2,'vinculo_id'=>9,'_retorno_tela'=>$a['referencia']])
            ->assertRetornoTela('/app/turmas/14?'.http_build_query(['secao'=>'acesso']+$rascunho));
        $this->alterarRecurso('POST','/app/turmas/14/professores',['usuario_id'=>2,'vinculo_id'=>9,'_retorno_tela'=>$b['referencia']])
            ->assertRetornoTela('/app/turmas/14?secao=acesso');
        Http::assertNotSent(fn($r)=>$r->method()==='POST' && $r->data()!==['usuario_id'=>2,'vinculo_id'=>9]);
    }
}
