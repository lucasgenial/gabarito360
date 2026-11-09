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
    private ?array $respostaArquivamento=null;
    private ?array $painelAlternativo=null;

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
            if($r->method()==='PUT'&&str_ends_with($p,'/arquivamento'))return Http::response(['data'=>$this->respostaArquivamento??['ativo'=>false,'arquivado'=>true,'versao'=>1,'alterado'=>true,'repetido'=>false]]);
            if($p==='/api/v1/turmas/14/painel/pdf')return Http::response('%PDF-1.7 documento fictício',200,['Content-Type'=>'application/pdf']);
            if($p===$this->falha)throw new ConnectionException('diagnostico-privado');
            $dados=match($p){
                '/api/v1/auth/me'=>$this->me,
                '/api/v1/auth/vinculos'=>[],
                '/api/v1/turmas/14/painel'=>['kpis'=>['alunos'=>2,'avaliacoes'=>1,'resultados'=>1,'acerto_pct'=>80],'recorte'=>['disciplina'=>'Todas','professor'=>'Todos'],'opcoes'=>['disciplinas'=>[],'professores'=>[]],'avaliacoes'=>[],'analise'=>['distribuicao_acertos'=>['total'=>0,'faixas'=>[]],'por_avaliacao'=>[],'alunos'=>['disponivel'=>true,'atencao'=>['total'=>6,'items'=>array_map(fn($i)=>['nome'=>'Fictício <'.$i.'>','resultados'=>1,'base_valida'=>1,'recuperacao'=>1,'media_pct'=>40],range(1,6))],'dentro_criterio'=>['total'=>0,'items'=>[]],'destaques'=>['total'=>0,'items'=>[],'empate_no_corte'=>false]]]],
                '/api/v1/turmas'=>['turmas'=>[['id'=>14,'escola_id'=>5,'nome'=>'Turma A','serie'=>'3','ano_letivo'=>2026],['id'=>15,'escola_id'=>5,'nome'=>'Turma B','serie'=>'3','ano_letivo'=>2026]]],
                '/api/v1/alunos/1'=>['id'=>1,'nome'=>'Aluno fictício','matricula'=>'F1','turma_id'=>14,'turma_nome'=>'Turma A','ativo'=>true,'turma'=>['id'=>14,'escola_id'=>5]],
                '/api/v1/turmas/14','/api/v1/turmas/15'=>['id'=>(int)basename($p),'nome'=>str_ends_with($p,'14')?'Turma A':'Turma B','serie'=>'3ª série','turno'=>'tarde','escola_id'=>5,'ano_letivo'=>2026,'ativo'=>true,'media_turma'=>null,'total_alunos'=>0,'total_provas'=>0,'alunos'=>[],'provas'=>[]],
                '/api/v1/turmas/14/disciplinas','/api/v1/turmas/15/disciplinas'=>['componentes'=>[],'gerir'=>$this->me['perfil']==='coordenador','atribuir'=>$this->me['perfil']==='coordenador'],
                '/api/v1/turmas/14/vinculo-docente/opcoes','/api/v1/turmas/15/vinculo-docente/opcoes'=>['disciplinas'=>[['id'=>31,'nome'=>'Matemática <teste>']], 'professores'=>[['vinculo_id'=>9,'nome'=>'Professor fictício']], 'completo'=>true],
                '/api/v1/disciplinas'=>['disciplinas'=>[['id'=>31,'nome'=>'Matemática <teste>','ativo'=>true]],'gerir'=>true],
                default=>['professores'=>[],'usuarios'=>[],'escolas'=>[]],
            };
            if($p==='/api/v1/turmas/14/painel'&&$this->painelAlternativo!==null)$dados=$this->painelAlternativo;
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

    public function test_visao_geral_adia_acesso_e_disciplinas_para_a_primeira_abertura(): void
    {
        $this->preparar();
        $html=$this->consultarTela('/app/turmas/14')->assertOk()->assertSee('data-turma-secao-adiada',false)
            ->assertSee('Carregando acesso à turma…')->assertSee('Carregando disciplinas e professores…')
            ->assertDontSee('id="professor-selecionado"',false)->assertDontSee('Vincular disciplina e professor')
            ->assertSee('turma-atribuicao.js',false)->assertSee('disciplinas.css',false)->getContent();
        foreach(['/api/v1/turmas/14/professores','/api/v1/usuarios','/api/v1/turmas/14/disciplinas','/api/v1/turmas/14/vinculo-docente/opcoes'] as $p)
            Http::assertNotSent(fn($r)=>parse_url($r->url(),PHP_URL_PATH)===$p);
        $this->assertSame(1,substr_count($html,'turma-atribuicao.js'));
        $this->assertStringNotContainsString('/turmas/14',preg_replace('~<noscript>.*?</noscript>~s','',$html));
        \Illuminate\Testing\TestResponse::fromBaseResponse(response($html))->assertDestinoRecurso('turmas.secao',['id'=>14],['secao'=>'acesso']);
        \Illuminate\Testing\TestResponse::fromBaseResponse(response($html))->assertDestinoRecurso('turmas.secao',['id'=>14],['secao'=>'disciplinas']);
    }

    public function test_secao_adiada_devolve_fragmento_sem_cache_com_formularios_ur4(): void
    {
        $this->preparar();
        $r=$this->consultarJson('/app/turmas/14/secao?secao=disciplinas')->assertOk();
        $this->assertStringContainsString('no-store',$r->headers->get('Cache-Control'));
        $this->assertStringContainsString('Vincular disciplina e professor',$r->json('html'));
        $this->assertStringContainsString('Matemática &lt;teste&gt;',$r->json('html'));
        Http::assertSent(fn($q)=>parse_url($q->url(),PHP_URL_PATH)==='/api/v1/turmas/14/vinculo-docente/opcoes');
        $this->assertNotEmpty($this->extrairFormularioRecurso('<html><body>'.$r->json('html').'</body></html>','turmas.atribuir',['id'=>14])['campos']['_recurso']);
        $acesso=$this->consultarJson('/app/turmas/14/secao?secao=acesso')->assertOk()->json('html');
        $this->assertStringContainsString('id="professores"',$acesso);$this->assertStringContainsString('Nenhum professor com acesso direto',$acesso);
        Http::assertSent(fn($q)=>parse_url($q->url(),PHP_URL_PATH)==='/api/v1/turmas/14/professores');
        $this->consultarJson('/app/turmas/14/secao?secao=provas')->assertStatus(422);
        $this->falha='/api/v1/turmas/14/professores';
        $this->assertStringContainsString('Não foi possível consultar os acessos',$this->consultarJson('/app/turmas/14/secao?secao=acesso')->assertOk()->json('html'));
    }

    public function test_secao_adiada_respeita_perfil_e_turma_negada(): void
    {
        $this->preparar('professor');
        $this->consultarJson('/app/turmas/14/secao?secao=disciplinas')->assertOk();
        Http::assertNotSent(fn($q)=>parse_url($q->url(),PHP_URL_PATH)==='/api/v1/turmas/14/vinculo-docente/opcoes');
        config(['disciplinas.ativo'=>false]);
        $this->consultarJson('/app/turmas/14/secao?secao=disciplinas')->assertNotFound();
        $this->falha='/api/v1/turmas/14';
        $this->consultarJson('/app/turmas/14/secao?secao=acesso')->assertStatus(503);
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

    public function test_painel_sem_recorte_tem_graficos_e_acoes_sem_card_de_instrucoes(): void
    {
        $this->preparar();
        $this->consultarTela('/app/turmas/14')->assertOk()->assertSee('turma-graficos.js')
            ->assertSee('Distribuição dos acertos')->assertSee('Mediana')->assertSee('data-documento-abrir="pdf"',false)
            ->assertDontSee('Dados filtrados')->assertDontSee('Saiba mais sobre os indicadores')->assertDontSee('Desativar')
            ->assertDontSee('Arquivar turma'); // Gerir não concede a permissão extra de arquivar.
    }

    public function test_analise_de_uma_avaliacao_nao_aparece_como_filtro_global(): void
    {
        $this->preparar();
        $this->consultarTela('/app/turmas/14?painel_avaliacao=8')->assertOk()->assertDontSee('Dados filtrados');
        Http::assertSent(fn($r)=>parse_url($r->url(),PHP_URL_PATH)==='/api/v1/turmas/14/painel'&&$r['analise_prova_id']==8);
    }

    public function test_documento_transmite_disposicao_e_modo_explicitos_pelo_corpo(): void
    {
        $this->preparar();
        foreach(['retrato','paisagem'] as $orientacao)foreach([0,1] as $baixar){
            $r=$this->alterarRecurso('POST','/app/turmas/14/painel/pdf',['orientacao'=>$orientacao,'baixar'=>$baixar,'painel_avaliacao'=>8]);
            $r->assertOk()->assertHeader('Content-Type','application/pdf')->assertHeader('Content-Disposition',($baixar?'attachment':'inline').'; filename="painel-turma.pdf"');
            Http::assertSent(fn($r)=>parse_url($r->url(),PHP_URL_PATH)==='/api/v1/turmas/14/painel/pdf'&&$r['orientacao']===$orientacao&&$r['analise_prova_id']==8&&!$r->offsetExists('baixar'));
        }
        $this->alterarRecurso('POST','/app/turmas/14/painel/pdf',['orientacao'=>'invalida'])->assertSessionHasErrors('orientacao');
    }

    public function test_arquivamento_bff_exige_permissao_e_envia_somente_estado_versionado(): void
    {
        $this->preparar();
        $d=['arquivado'=>true,'esperado_ativo'=>true,'versao'=>0,'chave'=>'11111111-1111-4111-8111-111111111111'];
        $this->alterarRecurso('PUT','/app/turmas/14/arquivamento',$d)->assertForbidden();
        $this->me['permissoes'][]='turma.desativar';$this->selecionarContextoFixture($this->me);
        $this->alterarRecurso('PUT','/app/turmas/14/arquivamento',$d+['usuario_id'=>99,'token'=>'nao-repassar'])->assertRetornoTela('/app/turmas/14?secao=visao-geral');
        Http::assertSent(fn($r)=>$r->method()==='PUT'&&parse_url($r->url(),PHP_URL_PATH)==='/api/v1/turmas/14/arquivamento'&&$r->data()===$d);
        $this->alterarRecurso('PUT','/app/turmas/14/arquivamento',['arquivado'=>true])->assertSessionHasErrors(['versao','chave','esperado_ativo']);
    }

    public function test_secretaria_pode_arquivar_somente_com_permissao_e_bff_recusa_resposta_incoerente(): void
    {
        $this->preparar('secretario_escolar');
        $this->me['permissoes']=['turma.ver','aluno.ver','turma.desativar','aluno.desativar'];$this->selecionarContextoFixture($this->me);
        $d=['arquivado'=>true,'esperado_ativo'=>true,'versao'=>0,'chave'=>'11111111-1111-4111-8111-111111111111'];
        foreach(['/app/turmas/14/arquivamento','/app/alunos/1/arquivamento']as$url){
            $destino=str_contains($url,'/turmas/')?'/app/turmas/14?secao=visao-geral':'/app/alunos/1';
            $this->alterarRecurso('PUT',$url,$d)->assertRetornoTela($destino)->assertSessionHas('sucesso','O registro foi arquivado. O histórico foi preservado.');
            foreach([[],['ativo'=>false,'arquivado'=>false,'versao'=>1,'alterado'=>true,'repetido'=>false],['ativo'=>false,'arquivado'=>true,'versao'=>'1','alterado'=>true,'repetido'=>false]]as$estado){
                session()->forget('sucesso');$this->respostaArquivamento=$estado;
                $this->alterarRecurso('PUT',$url,$d)->assertRetornoTela($destino)->assertSessionHas('erro','Não foi possível confirmar a resposta do arquivamento. Confira o estado atual antes de reenviar.')->assertSessionMissing('sucesso');
            }
            $this->respostaArquivamento=null;
        }
        $this->me['perfil']='auxiliar_secretaria';$this->me['permissoes']=['turma.ver','aluno.ver'];$this->selecionarContextoFixture($this->me);
        $this->alterarRecurso('PUT','/app/turmas/14/arquivamento',$d)->assertForbidden();
        $this->alterarRecurso('PUT','/app/alunos/1/arquivamento',$d)->assertForbidden();
    }

    public function test_acoes_tem_cores_textos_modal_proprio_e_acompanhamento_sem_diretiva_exposta():void
    {
        $this->preparar();$this->me['permissoes']=array_merge($this->me['permissoes'],['turma.desativar','aluno.ver','resultado.ver']);$this->selecionarContextoFixture($this->me);
        $this->consultarTela('/app/turmas/14')->assertOk()->assertSee('tu1r3-acao-editar')->assertSee('Editar turma')
            ->assertSee('data-arquivamento-modal',false)->assertSee('Arquivar turma')->assertSee('Destaques da turma')->assertSee('Alunos que precisam de atenção')
            ->assertSee('data-widget-id="alunos-destaques"',false)->assertSee('data-widget-id="alunos-atencao"',false)->assertDontSee('data-alunos-grupo',false)
            ->assertSee('Ver todos (6)')->assertSee('Fictício &lt;1&gt;',false)->assertDontSee('@if(',false)
            ->assertDontSee('Acerto por questão')->assertDontSee('window.confirm',false);
    }

    public function test_acompanhamento_nominal_exige_as_duas_permissoes_mesmo_se_upstream_envia_nomes():void
    {
        $this->preparar();
        $r=$this->consultarTela('/app/turmas/14')->assertOk()->assertDontSee('Fictício &lt;1&gt;',false)->assertDontSee('data-widget-id="alunos-destaques"',false)->assertDontSee('data-widget-id="alunos-atencao"',false);
        $dom=new \DOMDocument();@$dom->loadHTML($r->getContent());
        $grafico=(new \DOMXPath($dom))->query('//*[@data-painel-dados]')->item(0);
        $this->assertNotNull($grafico);
        $dados=json_decode($grafico->getAttribute('data-painel-dados'),true,512,JSON_THROW_ON_ERROR);
        $this->assertArrayNotHasKey('alunos',$dados['analise']);
        $this->assertEqualsCanonicalizing(['distribuicao_acertos','por_avaliacao'],array_keys($dados['analise']));
    }

    public function test_bases_dos_indicadores_sao_acessiveis_e_nao_injetam_html_da_avaliacao():void
    {
        $this->preparar();
        $this->painelAlternativo=['kpis'=>['alunos'=>2,'avaliacoes'=>1,'resultados'=>3,'alunos_com_resultado'=>2],
            'recorte'=>['disciplina'=>'Todas','professor'=>'Todos'],'opcoes'=>['disciplinas'=>[],'professores'=>[]],
            'avaliacoes'=>[],'situacoes_resultados'=>['total'=>3,'dentro_criterio'=>1,'indicacao_recuperacao'=>2,'outros'=>0],
            'analise'=>['resumo'=>['n'=>2,'media_pct'=>60,'mediana_pct'=>60,'min_pct'=>40,'max_pct'=>80],
                'distribuicao_acertos'=>['total'=>2,'percentuais_excluidos'=>1,'faixas'=>[]],
                'por_avaliacao'=>[['titulo'=>'Prova <img src=x onerror=alert(1)>','n'=>2,'media_pct'=>60,'mediana_pct'=>60,'resultados_total'=>3,'percentuais_excluidos'=>1,'abrangencia'=>'avaliacao_completa']],
                'alunos'=>['disponivel'=>false]]];
        $r=$this->consultarTela('/app/turmas/14')->assertOk()->assertSee('Base: 2 de 3 resultados; 1 sem percentual válido.')
            ->assertSee('Base: 3 resultados.')->assertSee('Consultado em')->assertSee('não a última alteração dos dados')
            ->assertSee('Cadastros distintos de alunos')->assertDontSee('<img src=x onerror=alert(1)>',false)
            ->assertDontSee('data-widget-id="leituras"',false)->assertDontSee('Pessoas distintas com resultado');
        $dom=new \DOMDocument();@$dom->loadHTML($r->getContent());$x=new \DOMXPath($dom);
        $this->assertSame(9,$x->query('//button[@data-painel-ajuda]')->length);
        foreach($x->query('//button[@data-painel-ajuda]') as $b){
            $target=$b->getAttribute('popovertarget');
            $this->assertSame($target,$b->getAttribute('aria-controls'));
            $this->assertSame(1,$x->query('//*[@id="'.$target.'" and @popover="auto"]')->length);
        }
    }

    public function test_arquivamento_retorna_destino_explicito_sem_depender_de_referer_ou_url_anterior():void
    {
        $this->preparar();$this->me['permissoes']=array_merge($this->me['permissoes'],['turma.desativar','aluno.desativar']);$this->selecionarContextoFixture($this->me);
        session(['_previous'=>['url'=>'http://localhost/app/favicon-16x16.png']]);
        $d=['arquivado'=>true,'esperado_ativo'=>true,'versao'=>0,'chave'=>'11111111-1111-4111-8111-111111111111'];
        $this->alterarRecurso('PUT','/app/turmas/14/arquivamento',$d)->assertRetornoTela('/app/turmas/14?secao=visao-geral');
        $origem=\App\Support\NavegacaoWeb::destino('turmas.show',['id'=>14,'secao'=>'alunos']);
        $this->alterarRecurso('PUT','/app/alunos/1/arquivamento',$d+['_retorno_tela'=>$origem['referencia']])->assertRetornoTela('/app/turmas/14?secao=alunos');
        $this->respostaArquivamento=[];
        $this->alterarRecurso('PUT','/app/turmas/14/arquivamento',$d)->assertRetornoTela('/app/turmas/14?secao=visao-geral')->assertSessionHas('erro');
    }

}
