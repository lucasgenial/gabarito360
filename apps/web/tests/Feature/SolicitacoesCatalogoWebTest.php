<?php
namespace Tests\Feature;
use App\Support\ContextoWeb;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class SolicitacoesCatalogoWebTest extends TestCase
{
    private const ID='11111111-1111-4111-8111-111111111111';
    private const ED='22222222-2222-4222-8222-222222222222';
    private function preparar(string $perfil='professor_autonomo'): array
    {
        config(['services.api.base_url'=>'http://api.test/api','catalogo.interface_ativa'=>true]);Http::preventStrayRequests();
        $this->withSession(['auth_token'=>'identidade-ficticia','auth_vinculos'=>true,'auth_nome'=>'Pessoa fictícia']);$this->withCookie(config('session.cookie'),session()->getId())->withCredentials();
        $interno=in_array($perfil,['suporte','financeiro'],true);
        $me=['token'=>'token-ficticio','tipo_contexto'=>$interno?'interno':'vinculo','perfil'=>$perfil,
            'espaco'=>['id'=>1,'tipo'=>$perfil==='professor_autonomo'?'pessoal':'escola','nome'=>'Espaço fictício'],
            'permissoes'=>match($perfil){'suporte'=>['catalogo.solicitacao.analisar','catalogo.manter_global'],'dir_escolar'=>['catalogo.manter_escopo'],'financeiro'=>['cliente.ver'],default=>['catalogo.consultar','catalogo.solicitar']}];
        return [ContextoWeb::criar($me),$me];
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
        $this->get('/instituicoes/solicitacoes?_ctx='.$ctx)->assertOk()->assertSee('Solicitar cadastro')->assertSee('Próxima');
        $this->get('/instituicoes/solicitacoes/nova?_ctx='.$ctx)->assertOk()->assertSee('name="chave"',false)->assertSee('data-catalogo-localizacao',false)->assertDontSee('Registrar revisão cadastral');
        $this->get('/instituicoes/solicitacoes/'.self::ID.'?_ctx='.$ctx)->assertOk()->assertSee('Enviar complementação')->assertDontSee('<script>fictícia</script>',false)->assertDontSee('Cadastrar nova referência e aprovar');
        $this->post('/instituicoes/solicitacoes',['_ctx'=>$ctx,'chave'=>self::ED,'cadastro'=>['nome'=>'Fictícia']])->assertRedirect();
        Http::assertSent(fn($r)=>$r->method()==='POST' && str_ends_with($r->url(),'/catalogo/minhas-solicitacoes') && $r['chave']===self::ED && !isset($r['_ctx']) && !isset($r['_token']) && $r->hasHeader('Authorization','Bearer token-ficticio'));
    }
    public function test_suporte_tem_fila_busca_e_cadastro_com_fonte_explicita(): void {
        [$ctx,$me]=$this->preparar('suporte');$this->fake($me);
        $this->get('/catalogo/atendimento?_ctx='.$ctx)->assertOk()->assertSee('Solicitações de instituições')->assertSee('Cadastrar referência');
        $this->get('/catalogo/atendimento/'.self::ID.'?_ctx='.$ctx)->assertOk()->assertSee('Pedir complementação')->assertSee('Cadastrar nova referência e aprovar');
        $this->get('/catalogo/atendimento/buscar?_ctx='.$ctx.'&uf=BA&solicitacao='.self::ID)->assertOk()->assertSee('Cadastro da plataforma')->assertSee('Aprovar com esta instituição')->assertDontSee('Censo Superior');
        $this->get('/catalogo/atendimento/autoridades/'.self::ID.'?_ctx='.$ctx)->assertOk()->assertSee('Escola operacional fictícia')->assertSee('Registrar alteração de permissão');
    }
    public function test_gestao_limitada_nao_alcanca_fila_pessoal_ou_interna(): void {
        [$ctx,$me]=$this->preparar('dir_escolar');$this->fake($me);
        $this->get('/catalogo/gestao?_ctx='.$ctx)->assertOk()->assertSee('Revisar cadastro')->assertDontSee('Observação privada fictícia');
        $this->get('/catalogo/atendimento?_ctx='.$ctx)->assertForbidden();$this->get('/instituicoes/solicitacoes?_ctx='.$ctx)->assertForbidden();
        $this->post('/catalogo/gestao/instituicoes/'.self::ID,['_ctx'=>$ctx,'edicao_id'=>self::ED,'cadastro'=>['nome'=>'Nome fictício'],'motivo'=>'Motivo fictício','conferencia'=>1,'situacao'=>'1'])->assertRedirect();
        Http::assertSent(fn($r)=>$r->method()==='POST' && str_ends_with($r->url(),'/catalogo/gestao/instituicoes/'.self::ID));
    }
    public function test_financeiro_e_flag_desligada_bloqueiam_telas(): void {
        [$ctx,$me]=$this->preparar('financeiro');$this->fake($me);$this->get('/catalogo/atendimento?_ctx='.$ctx)->assertForbidden();
        Http::assertNotSent(fn($r)=>str_contains($r->url(),'/catalogo/'));config(['catalogo.interface_ativa'=>false]);$this->get('/catalogo/atendimento?_ctx='.$ctx)->assertNotFound();
    }
    public function test_indisponibilidade_nao_parece_fila_vazia_e_reenvio_preserva_intencao(): void {
        [$ctx,$me]=$this->preparar();Http::fake(fn($r)=>str_ends_with($r->url(),'/auth/me')?Http::response(['data'=>$me]):Http::response(['message'=>'SEGREDO-FICTICIO'],500));
        $this->get('/instituicoes/solicitacoes?_ctx='.$ctx)->assertStatus(503)->assertDontSee('Nenhuma solicitação encontrada')->assertDontSee('SEGREDO-FICTICIO');
        Http::fake(fn($r)=>str_ends_with($r->url(),'/auth/me')?Http::response(['data'=>$me]):Http::failedConnection());
        $this->post('/instituicoes/solicitacoes',['_ctx'=>$ctx,'chave'=>self::ED,'cadastro'=>['nome'=>'Fictícia']])->assertRedirect()->assertSessionHasInput('chave',self::ED)->assertSessionHas('erro');
    }
    public function test_erro_de_campo_preserva_formulario_e_site_da_autoridade_na_raiz(): void {
        [$ctx,$me]=$this->preparar();
        Http::fake(fn($r)=>str_ends_with($r->url(),'/auth/me')?Http::response(['data'=>$me]):Http::response(['message'=>'Confira os dados','errors'=>['site'=>['Informe HTTPS.']]],422));
        $this->from('/instituicoes/solicitacoes/nova?_ctx='.$ctx)->post('/instituicoes/solicitacoes',['_ctx'=>$ctx,'chave'=>self::ED,'cadastro'=>['nome'=>'Instituição fictícia','site'=>'http://example.test']])->assertRedirect()->assertSessionHasErrors('cadastro.site')->assertSessionHasInput('cadastro.nome','Instituição fictícia');
        [$ctx,$me]=$this->preparar('suporte');
        Http::fake(fn($r)=>str_ends_with($r->url(),'/auth/me')?Http::response(['data'=>$me]):Http::response(['message'=>'Confira os dados','errors'=>['site'=>['Informe HTTPS.']]],422));
        $this->post('/catalogo/atendimento/autoridades',['_ctx'=>$ctx,'site'=>'http://example.test'])->assertRedirect()->assertSessionHasErrors('site');
    }
}
