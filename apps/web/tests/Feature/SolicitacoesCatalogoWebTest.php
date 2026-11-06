<?php
namespace Tests\Feature;
use App\Support\ContextoWeb;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class SolicitacoesCatalogoWebTest extends TestCase
{
    use \Tests\Support\ContextoSelecionado;
    private const ID='11111111-1111-4111-8111-111111111111';
    private const ED='22222222-2222-4222-8222-222222222222';
    private function preparar(string $perfil='professor_autonomo'): array
    {
        config(['services.api.base_url'=>'http://api.test/api','catalogo.interface_ativa'=>true]);Http::preventStrayRequests();
        $this->withSession(['auth_token'=>'identidade-ficticia','auth_vinculos'=>true,'auth_nome'=>'Pessoa fictícia']);$this->withCookie(config('session.cookie'),session()->getId())->withCredentials();
        $interno=in_array($perfil,['suporte','financeiro'],true);
        $me=['vinculo_id'=>$interno?null:1,'acesso_interno_id'=>$interno?1:null,'token'=>'token-ficticio','tipo_contexto'=>$interno?'interno':'vinculo','perfil'=>$perfil,
            'espaco'=>['id'=>1,'tipo'=>$perfil==='professor_autonomo'?'pessoal':'escola','nome'=>'Espaço fictício'],
            'permissoes'=>match($perfil){'suporte'=>['catalogo.solicitacao.analisar','catalogo.manter_global'],'dir_escolar'=>['catalogo.manter_escopo'],'financeiro'=>['cliente.ver'],default=>['catalogo.consultar','catalogo.solicitar']}];
        $this->selecionarContextoFixture($me);return [ContextoWeb::referencia($me),$me];
    }
    private function pedido(string $situacao='em_analise'): array {return ['id'=>self::ID,'situacao'=>$situacao,'versao'=>2,'unidade_id'=>self::ID,'edicao_id'=>self::ED,'criado_em'=>'2026-11-03 12:00:00','atualizado_em'=>'2026-11-03 12:10:00','dados'=>['nome'=>'Instituição <script>fictícia</script>','tipo'=>'escola','uf'=>'BA','municipio_ibge'=>'2900001','dependencia'=>'estadual','observacao'=>'Observação privada fictícia'],'eventos'=>[['ocorrido_em'=>'2026-11-03 12:10:00','mensagem'=>'Em análise fictícia','acao'=>'analisar','versao'=>2]],'avisos'=>[]];}
    private function e(): array {return ['unidade_id'=>self::ID,'edicao_id'=>self::ED,'nome'=>'Instituição manual fictícia','sigla'=>'IMF','namespace'=>'plataforma','codigo'=>'01K00000000000000000000000','tipo'=>'escola','natureza'=>'escola','dependencia'=>'estadual','municipio_nome'=>'Município fictício','municipio_ibge'=>'2900001','uf'=>'BA','fonte'=>'manual','ano'=>2026,'situacao_rotulo'=>'Ativa no cadastro da plataforma','elegivel'=>true];}
    private function fake(array $me,string $estado='em_analise'): void {
        Http::fake(function($r)use($me,$estado){
            $path=parse_url($r->url(),PHP_URL_PATH);$p=['atual'=>1,'ultima'=>2,'total'=>21,'por_pagina'=>20];
            if(str_ends_with($path,'/auth/me'))$d=$me;
            elseif(str_ends_with($path,'/opcoes'))$d=['ufs'=>[['sigla'=>'BA','nome'=>'Bahia']],'escolas'=>[['id'=>1,'nome'=>'Escola operacional fictícia','inep'=>'29000001']],'autoridades'=>[]];
            elseif(str_contains($path,'/instituicoes/'))$d=$this->e();
            elseif(str_ends_with($path,'/'.self::ID))$d=$this->pedido($estado);
            elseif(str_ends_with($path,'/catalogo'))$d=['itens'=>[$this->e()],'paginacao'=>$p];
            elseif($r->method()==='POST')$d=['id'=>self::ID,'situacao'=>'enviada','versao'=>1];
            else $d=['itens'=>str_ends_with($path,'/gestao')?[$this->e()]:[$this->pedido($estado)],'paginacao'=>$p];
            return Http::response(['data'=>$d]);
        });
    }
    public function test_pessoal_lista_formulario_complementacao_e_post_contextualizado(): void {
        [$ctx,$me]=$this->preparar();$this->fake($me,'aguardando_complementacao');
        $this->get('/app/instituicoes/solicitacoes')->assertOk()->assertSee('Solicitar cadastro')->assertSee('Próxima');
        $this->get('/app/instituicoes/solicitacoes/nova')->assertOk()->assertSee('name="chave"',false)->assertSee('data-catalogo-localizacao',false)->assertDontSee('Registrar revisão cadastral');
        $this->get('/app/instituicoes/solicitacoes/'.self::ID)->assertOk()->assertSee('Enviar complementação')->assertDontSee('<script>fictícia</script>',false)->assertDontSee('Cadastrar nova referência e aprovar');
        $this->post('/app/instituicoes/solicitacoes',['chave'=>self::ED,'cadastro'=>['nome'=>'Fictícia'],'_token'=>session()->token(),'_revisao'=>session('auth_contexto_revisao')])->assertRedirect();
        Http::assertSent(fn($r)=>$r->method()==='POST' && str_ends_with($r->url(),'/catalogo/minhas-solicitacoes') && $r['chave']===self::ED && !isset($r['_ctx']) && !isset($r['_revisao']) && !isset($r['_token']) && $r->hasHeader('Authorization','Bearer token-ficticio'));
    }
    public function test_suporte_tem_fila_busca_e_cadastro_com_fonte_explicita(): void {
        [$ctx,$me]=$this->preparar('suporte');$this->fake($me);
        $this->get('/app/catalogo/atendimento')->assertOk()->assertSee('Solicitações de instituições')->assertSee('Cadastrar referência');
        $this->get('/app/catalogo/atendimento/'.self::ID)->assertOk()->assertSee('Pedir complementação')->assertSee('Cadastrar nova referência e aprovar');
        $this->get('/app/catalogo/atendimento/buscar?uf=BA&solicitacao='.self::ID)->assertOk()->assertSee('Cadastro da plataforma')->assertSee('Aprovar com esta instituição')->assertDontSee('Censo Superior');
        $this->get('/app/catalogo/atendimento/autoridades/'.self::ID)->assertOk()->assertSee('Escola operacional fictícia')->assertSee('Registrar alteração de permissão');
    }
    public function test_gestao_limitada_nao_alcanca_fila_pessoal_ou_interna(): void {
        [$ctx,$me]=$this->preparar('dir_escolar');$this->fake($me);
        $this->get('/app/catalogo/gestao')->assertOk()->assertSee('Revisar cadastro')->assertDontSee('Observação privada fictícia');
        $this->get('/app/catalogo/atendimento')->assertForbidden();$this->get('/app/instituicoes/solicitacoes')->assertForbidden();
        $this->post('/app/catalogo/gestao/instituicoes/'.self::ID,['edicao_id'=>self::ED,'cadastro'=>['nome'=>'Nome fictício'],'motivo'=>'Motivo fictício','conferencia'=>1,'situacao'=>'1'])->assertRedirect();
        Http::assertSent(fn($r)=>$r->method()==='POST' && str_ends_with($r->url(),'/catalogo/gestao/instituicoes/'.self::ID));
    }
    public function test_financeiro_e_flag_desligada_bloqueiam_telas(): void {
        [$ctx,$me]=$this->preparar('financeiro');$this->fake($me);$this->get('/app/catalogo/atendimento')->assertForbidden();
        Http::assertNotSent(fn($r)=>str_contains($r->url(),'/catalogo/'));config(['catalogo.interface_ativa'=>false]);$this->get('/app/catalogo/atendimento')->assertNotFound();
    }
    public function test_indisponibilidade_nao_parece_fila_vazia_e_reenvio_preserva_intencao(): void {
        [$ctx,$me]=$this->preparar();Http::fake(fn($r)=>str_ends_with($r->url(),'/auth/me')?Http::response(['data'=>$me]):Http::response(['message'=>'SEGREDO-FICTICIO'],500));
        $this->get('/app/instituicoes/solicitacoes')->assertStatus(503)->assertDontSee('Nenhuma solicitação encontrada')->assertDontSee('SEGREDO-FICTICIO');
        Http::fake(fn($r)=>str_ends_with($r->url(),'/auth/me')?Http::response(['data'=>$me]):Http::failedConnection());
        $this->post('/app/instituicoes/solicitacoes',['chave'=>self::ED,'cadastro'=>['nome'=>'Fictícia']])->assertRedirect()->assertSessionHasInput('chave',self::ED)->assertSessionHas('erro');
    }
    public function test_erro_de_campo_preserva_formulario_e_site_da_autoridade_na_raiz(): void {
        [$ctx,$me]=$this->preparar();
        Http::fake(fn($r)=>str_ends_with($r->url(),'/auth/me')?Http::response(['data'=>$me]):Http::response(['message'=>'Confira os dados','errors'=>['site'=>['Informe HTTPS.']]],422));
        $this->from('/app/instituicoes/solicitacoes/nova')->post('/app/instituicoes/solicitacoes',['chave'=>self::ED,'cadastro'=>['nome'=>'Instituição fictícia','site'=>'http://example.test']])->assertRedirect()->assertSessionHasErrors('cadastro.site')->assertSessionHasInput('cadastro.nome','Instituição fictícia');
    }
    public function test_site_da_autoridade_mantem_erro_na_raiz(): void {
        [$ctx,$me]=$this->preparar('suporte');
        Http::fake(fn($r)=>str_ends_with($r->url(),'/auth/me')?Http::response(['data'=>$me]):Http::response(['message'=>'Confira os dados','errors'=>['site'=>['Informe HTTPS.']]],422));
        $this->post('/app/catalogo/atendimento/autoridades',['site'=>'http://example.test'])->assertRedirect()->assertSessionHasErrors('site');
    }
    public function test_filtros_invalidos_voltam_a_busca_com_valores_e_erros(): void {
        [$ctx,$me]=$this->preparar('suporte');
        Http::fake(function($r)use($me){
            if(str_ends_with($r->url(),'/auth/me'))return Http::response(['data'=>$me]);
            if(str_contains($r->url(),'/catalogo?'))return Http::response(['errors'=>['codigo'=>['Código incompatível com a fonte.']]],422);
            return Http::response(['data'=>['ufs'=>[['sigla'=>'BA','nome'=>'Bahia']]]]);
        });
        $this->get('/app/catalogo/atendimento/buscar?tipo=escola')->assertStatus(422)->assertSee('Consultar catálogo institucional')->assertSee('Código incompatível com a fonte.')->assertSee('name="tipo"',false);
        $this->get('/app/catalogo/atendimento/buscar?namespace=inep_escola&codigo=ABC')->assertStatus(422)->assertSee('value="ABC"',false)->assertSee('aria-invalid="true"',false);
    }
    public function test_formulario_ssr_catalogo_envia_somente_negocio_com_csrf_real(): void {
        [$ctx,$me]=$this->preparar();$this->fake($me);
        $html=$this->get('/app/instituicoes/solicitacoes/nova')->assertOk()->getContent();
        preg_match('/name="_token" value="([^"]+)"/',$html,$csrf);
        preg_match('/name="_revisao" value="([^"]+)"/',$html,$rev);
        $this->assertNotEmpty($csrf[1]);$this->assertNotEmpty($rev[1]);
        $this->flushHeaders();$this->app->instance('env','local');
        $d=['chave'=>self::ED,'cadastro'=>['nome'=>'Fictícia'],'_token'=>$csrf[1],'_revisao'=>$rev[1]];
        $this->post('/app/instituicoes/solicitacoes',array_replace($d,['_token'=>'invalido']))->assertStatus(419);
        Http::assertNotSent(fn($r)=>$r->method()==='POST');
        $this->post('/app/instituicoes/solicitacoes',$d)->assertRedirect();
        Http::assertSent(fn($r)=>$r->method()==='POST' && $r->data()===['chave'=>self::ED,'cadastro'=>['nome'=>'Fictícia']]);
    }

}
