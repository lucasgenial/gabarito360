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
            'permissoes'=>$perfil==='coordenador'?['turma.ver','turma.gerir','turma.vincular_professor','disciplina.gerir','aluno.gerir','relatorio.turma']:['turma.ver'],
            'espaco'=>['id'=>5,'tipo'=>'escola','nome'=>'Escola fictícia']];
        $this->selecionarContextoFixture($this->me);
        Http::fake(function($r){
            $p=parse_url($r->url(),PHP_URL_PATH);
            if($p===$this->falha)throw new ConnectionException('diagnostico-privado');
            $dados=match($p){
                '/api/v1/auth/me'=>$this->me,
                '/api/v1/auth/vinculos'=>[],
                '/api/v1/turmas/14/painel'=>['kpis'=>['alunos'=>2,'avaliacoes'=>1,'resultados'=>1,'acerto_pct'=>80],'recorte'=>['disciplina'=>'Todas','professor'=>'Todos'],'opcoes'=>['disciplinas'=>[],'professores'=>[]],'avaliacoes'=>[]],
                '/api/v1/turmas'=>['turmas'=>[['id'=>14,'escola_id'=>5,'nome'=>'Turma A','serie'=>'3','ano_letivo'=>2026],['id'=>15,'escola_id'=>5,'nome'=>'Turma B','serie'=>'3','ano_letivo'=>2026]]],
                '/api/v1/alunos/1'=>['id'=>1,'nome'=>'Aluno fictício','matricula'=>'F1','turma_id'=>14,'turma_nome'=>'Turma A','ativo'=>true,'turma'=>['id'=>14,'escola_id'=>5]],
                '/api/v1/turmas/14','/api/v1/turmas/15'=>['id'=>(int)basename($p),'nome'=>str_ends_with($p,'14')?'Turma A':'Turma B','serie'=>'3ª série','turno'=>'tarde','escola_id'=>5,'ano_letivo'=>2026,'ativo'=>true,'media_turma'=>null,'total_alunos'=>0,'total_provas'=>0,'alunos'=>[],'provas'=>[]],
                '/api/v1/turmas/14/disciplinas','/api/v1/turmas/15/disciplinas'=>['componentes'=>[],'gerir'=>$this->me['perfil']==='coordenador','atribuir'=>$this->me['perfil']==='coordenador'],
                '/api/v1/turmas/14/vinculo-docente/opcoes','/api/v1/turmas/15/vinculo-docente/opcoes'=>['disciplinas'=>[['id'=>31,'nome'=>'Matemática <teste>']], 'professores'=>[['vinculo_id'=>9,'nome'=>'Professor fictício']], 'completo'=>true],
                '/api/v1/disciplinas'=>['disciplinas'=>[['id'=>31,'nome'=>'Matemática <teste>','ativo'=>true]],'gerir'=>true],
                default=>['professores'=>[],'usuarios'=>[],'escolas'=>[]],
            };
            if($r->method()==='POST')$dados=['id'=>9];
            return Http::response(['data'=>$dados,'meta'=>['pagina'=>2,'paginas'=>3,'total'=>45]],$r->method()==='POST'?201:200);
        });
    }

    public function test_turma_tem_paineis_e_dois_selects_sem_busca_disciplina(): void
    {
        $this->preparar();
        $r=$this->consultarTela('/app/turmas/14?secao=disciplinas')->assertOk()
            ->assertSee('data-secao-inicial="disciplinas"',false)->assertSee('Vincular disciplina e professor')
            ->assertSee('Matemática &lt;teste&gt;',false)->assertDontSee('Matemática <teste>',false)
            ->assertSee('name="disciplina_id"',false)->assertSee('name="professor_vinculo_id"',false)
            ->assertDontSee('name="catalogo_busca"',false)->assertDontSee('name="inicio_em"',false);
        foreach(['visao-geral','alunos','disciplinas','provas','acesso']as$secao)$r->assertSee('data-turma-painel="'.$secao.'"',false);
        Http::assertSent(fn($r)=>parse_url($r->url(),PHP_URL_PATH)==='/api/v1/turmas/14/vinculo-docente/opcoes');
        Http::assertNotSent(fn($r)=>$r->method()!=='GET'||parse_url($r->url(),PHP_URL_PATH)==='/api/v1/disciplinas');
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
        $this->preparar();$this->falha='/api/v1/turmas/14/vinculo-docente/opcoes';
        $this->consultarTela('/app/turmas/14?secao=disciplinas')->assertOk()->assertSee('Não foi possível consultar as opções')
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

    public function test_cadastro_aluno_volta_para_aba_alunos_e_preview_sem_javascript_inline(): void
    {
        $this->preparar();
        $r=$this->consultarTela('/app/alunos/novo?turma_id=14')->assertOk()->assertSee('aluno-cadastro.js')->assertDontSee('oninput=',false)->assertDontSee('onchange=',false);
        $r->assertDestinoRecurso('turmas.show',['id'=>14],['secao'=>'alunos']);
        $this->alterarRecurso('POST','/app/alunos',['turma_id'=>14,'nome'=>'Aluno fictício','matricula'=>'F1','data_nascimento'=>'2010-01-01'])->assertRetornoTela('/app/turmas/14?secao=alunos');
    }

    public function test_edicao_turma_em_pagina_completa_sem_modal_e_erros_retornam_formulario(): void
    {
        $this->preparar();
        $this->consultarTela('/app/turmas/14')->assertOk()->assertDontSee('id="modal-editar"',false)->assertDestinoRecurso('turmas.edit',['id'=>14]);
        $this->consultarTela('/app/turmas/14/editar')->assertOk()->assertSee('Salvar alterações')->assertSee('name="ano_letivo"',false)->assertDontSee('role="dialog"',false);
        $this->alterarRecurso('PUT','/app/turmas/14',['nome'=>'','serie'=>'3','turno'=>'tarde'])->assertRetornoTela('/app/turmas/14/editar')->assertSessionHasErrors('nome');
    }

    public function test_editar_aluno_tem_retorno_a_turma_e_transferencia_recusa_professor(): void
    {
        $this->preparar();
        $this->consultarTela('/app/alunos/1/editar')->assertOk()->assertSee('Salvar alterações')->assertDestinoRecurso('turmas.show',['id'=>14],['secao'=>'alunos']);
        $this->alterarRecurso('PUT','/app/alunos/1',['nome'=>'','matricula'=>'F1','voltar_turma'=>1])->assertRetornoTela('/app/alunos/1/editar')->assertSessionHasErrors('nome');
        $this->preparar('professor');
        $this->consultarTela('/app/alunos/1/transferir')->assertForbidden();
        $this->alterarRecurso('POST','/app/alunos/1/transferencias-turma',['turma_id'=>15])->assertForbidden();
    }
    public function test_par_docente_usa_um_post_aditivo_e_nao_vaza_transporte(): void
    {
        $this->preparar();
        $d=['disciplina_id'=>31,'professor_vinculo_id'=>9,'chave_idempotencia'=>'11111111-1111-4111-8111-111111111111'];
        $this->alterarRecurso('POST','/app/turmas/14/atribuicoes',$d)->assertRetornoTela('/app/turmas/14?secao=disciplinas');
        Http::assertSent(fn($r)=>$r->method()==='POST'&&parse_url($r->url(),PHP_URL_PATH)==='/api/v1/turmas/14/vinculos-docentes'&&$r->data()===$d);
        Http::assertNotSent(fn($r)=>$r->method()==='POST'&&parse_url($r->url(),PHP_URL_PATH)!=='/api/v1/turmas/14/vinculos-docentes');
    }

    public function test_filtros_invalidos_reabrem_modal_e_preservam_recorte_aplicado(): void
    {
        $this->preparar();
        $r=$this->consultarTela('/app/turmas/14?painel_inicio=invalido&painel_anterior[painel_inicio]=2026-01-01');
        $r->assertOk()->assertSee('data-painel-erros="1"',false)->assertSee('Os indicadores abaixo mantêm o recorte anterior.')
            ->assertSee('01/01/2026')->assertSee('aria-describedby="erro-painel-inicio"',false);
        Http::assertSent(fn($r)=>parse_url($r->url(),PHP_URL_PATH)==='/api/v1/turmas/14/painel'&&$r['inicio']==='2026-01-01');
        Http::assertNotSent(fn($r)=>parse_url($r->url(),PHP_URL_PATH)==='/api/v1/turmas/14/painel'&&$r['inicio']==='invalido');
    }

    public function test_modal_transferencia_consulta_sem_gravar_e_com_acao_protegida_ur4(): void
    {
        $this->preparar();
        $r=$this->withHeader('X-G360-Modal','transferencia')->consultarTela('/app/alunos/1/transferir')
            ->assertOk()->assertSee('data-transferencia-conteudo',false)->assertSee('data-transferencia-revisao',false)
            ->assertSee('Revisar transferência')->assertDontSee('<html',false);
        $f=$this->extrairFormularioRecurso($r->getContent(),'alunos.transferir',['id'=>1]);
        $this->assertStringNotContainsString('/1/',$f['url']);
        Http::assertNotSent(fn($r)=>$r->method()!=='GET');
    }

    public function test_transferencia_ajax_retoma_aba_alunos_sem_id_na_url(): void
    {
        $this->preparar();
        $r=$this->alterarRecursoJson('POST','/app/alunos/1/transferencias-turma',['turma_id'=>15,'turma_origem_id'=>14,'chave'=>'11111111-1111-4111-8111-111111111111','motivo'=>'Solicitação de teste']);
        $r->assertOk()->assertJsonPath('destino.pagina','/app/turmas/detalhes');
        $d=\App\Support\NavegacaoWeb::obter($r->json('destino.referencia'));
        $this->assertSame(15,(int)$d['parametros']['id']);$this->assertSame('alunos',$d['consulta']['secao']);
        $this->assertFalse(session()->has('sucesso'));
        $this->consultarTela('/app/turmas/14')->assertOk()->assertDontSee('Aluno transferido. O histórico anterior foi preservado.');
        \App\Support\NavegacaoWeb::mensagens($r->json('destino.referencia'));
        $this->assertSame('Aluno transferido. O histórico anterior foi preservado.',session('sucesso'));
        Http::assertSent(fn($r)=>parse_url($r->url(),PHP_URL_PATH)==='/api/v1/alunos/1/transferencias-turma'&&$r['motivo']==='Solicitação de teste'&&!$r->offsetExists('_token'));
        $this->alterarRecursoJson('POST','/app/alunos/1/transferencias-turma',['turma_id'=>15])->assertUnprocessable()->assertJsonValidationErrors(['motivo','chave','turma_origem_id']);
    }

}
