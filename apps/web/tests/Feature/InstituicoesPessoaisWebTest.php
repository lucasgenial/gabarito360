<?php
namespace Tests\Feature;

use App\Support\{Acesso,ContextoWeb};
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class InstituicoesPessoaisWebTest extends TestCase
{
    private const UNIDADE='11111111-1111-4111-8111-111111111111';
    private const EDICAO='22222222-2222-4222-8222-222222222222';
    private function preparar(string $perfil='professor_autonomo'): array {
        config(['services.api.base_url'=>'http://api.test/api','catalogo.interface_ativa'=>true]);Http::preventStrayRequests();
        $this->withSession(['auth_token'=>'identidade','auth_vinculos'=>true,'auth_nome'=>'Pessoa fictícia']);
        $this->withCookie(config('session.cookie'),session()->getId())->withCredentials();
        $me=['token'=>'contexto-pessoal','perfil'=>$perfil,'espaco'=>['id'=>1,'tipo'=>$perfil==='professor_autonomo'?'pessoal':'escola','nome'=>'Pessoal fictício'],'permissoes'=>['catalogo.consultar','catalogo.referencia_pessoal.gerir']];
        return [ContextoWeb::criar($me),$me];
    }
    private function e(): array { return ['unidade_id'=>self::UNIDADE,'instituicao_id'=>'33333333-3333-4333-8333-333333333333','nome'=>'Escola <script>ficticia</script>','sigla'=>'EF','natureza'=>'escola','tipo'=>'escola','dependencia'=>'estadual','uf'=>'BA','municipio_nome'=>'Porto fictício','municipio_ibge'=>'2900001','namespace'=>'inep_escola','codigo'=>'29000001','situacao_fonte'=>'1','situacao_rotulo'=>'Em atividade na fonte','elegivel'=>true,'edicao_id'=>self::EDICAO,'ano'=>2025,'fonte'=>'basica']; }
    private function pag(): array { return ['atual'=>1,'ultima'=>2,'por_pagina'=>20,'total'=>21]; }
    public function test_fluxo_busca_confirmacao_lista_e_contexto_sem_injetar_escopo(): void {
        [$ctx,$me]=$this->preparar();
        Http::fake(function($r)use($me){
            $this->assertTrue($r->hasHeader('Authorization','Bearer contexto-pessoal'));
            $path=parse_url($r->url(),PHP_URL_PATH);
            $d=match($path){
                '/api/v1/auth/me'=>$me,
                '/api/v1/catalogo/ufs'=>['itens'=>[['sigla'=>'BA','nome'=>'Bahia','codigo_ibge'=>29]]],
                '/api/v1/catalogo/municipios'=>['itens'=>[['codigo_ibge'=>'2900001','nome'=>'Porto fictício','uf'=>'BA']],'paginacao'=>$this->pag()],
                '/api/v1/catalogo/instituicoes'=>['itens'=>[$this->e()],'paginacao'=>$this->pag()],
                '/api/v1/catalogo/instituicoes/'.self::UNIDADE=>$this->e(),
                default=> $r->method()==='POST' ? ['id'=>self::UNIDADE,'situacao'=>'ativa','versao'=>1] : ['itens'=>[['id'=>self::UNIDADE,'instituicao'=>$this->e(),'situacao'=>'ativa','versao'=>1,'edicao_corrente'=>true,'elegivel_atualmente'=>true]],'paginacao'=>$this->pag()],
            };return Http::response(['data'=>$d]);
        });
        $this->get('/minhas-instituicoes/buscar?_ctx='.$ctx.'&uf=BA&municipio_ibge=2900001&tipo=escola')->assertOk()->assertSee('Conferir instituição')->assertSee('Próxima')->assertSee('INEP da escola')->assertDontSee('<script>ficticia</script>',false);
        $this->get('/minhas-instituicoes/conferir/'.self::UNIDADE.'?_ctx='.$ctx.'&edicao_id='.self::EDICAO)->assertOk()->assertSee('não confirma vínculo profissional')->assertSee('name="confirmacao"',false)->assertDontSee(' checked',false);
        $this->post('/minhas-instituicoes',['_ctx'=>$ctx,'unidade_id'=>self::UNIDADE,'edicao_id'=>self::EDICAO,'confirmacao'=>1,'espaco_id'=>999])->assertRedirect();
        Http::assertSent(fn($r)=>$r->method()==='POST' && $r['unidade_id']===self::UNIDADE && !isset($r['espaco_id']) && !isset($r['_ctx']));
        $this->get('/minhas-instituicoes?_ctx='.$ctx)->assertOk()->assertSee('Minhas instituições')->assertSee('Arquivar referência')->assertDontSee('href="'.route('escolas.index').'"',false);
    }
    public function test_erro_api_e_rede_nao_sao_exibidos_como_resultado_vazio(): void {
        [$ctx,$me]=$this->preparar();
        Http::fake(fn($r)=>str_ends_with($r->url(),'/auth/me')?Http::response(['data'=>$me]):Http::response(['message'=>'SEGREDO-INTERNO'],500));
        $this->get('/minhas-instituicoes/buscar?_ctx='.$ctx.'&uf=BA')->assertStatus(503)->assertDontSee('Nenhuma instituição encontrada')->assertDontSee('SEGREDO-INTERNO');
        Http::fake(fn($r)=>str_ends_with($r->url(),'/auth/me')?Http::response(['data'=>$me]):Http::failedConnection());
        $this->getJson('/minhas-instituicoes/municipios?_ctx='.$ctx.'&uf=BA')->assertStatus(503);
    }
    public function test_flag_e_contexto_institucional_fecham_tela_e_menu(): void {
        [$ctx,$me]=$this->preparar('professor');Http::fake(['*'=>Http::response(['data'=>$me])]);
        $this->get('/minhas-instituicoes?_ctx='.$ctx)->assertForbidden();
        Http::assertNotSent(fn($r)=>str_contains($r->url(),'/catalogo/'));
        config(['catalogo.interface_ativa'=>false]);$this->get('/minhas-instituicoes?_ctx='.$ctx)->assertNotFound();
    }
    public function test_edicao_mudou_exige_nova_busca_sem_selecao_silenciosa(): void {
        [$ctx,$me]=$this->preparar();Http::fake(fn($r)=>str_ends_with($r->url(),'/auth/me')?Http::response(['data'=>$me]):Http::response(['message'=>'Edição mudou'],409));
        $this->post('/minhas-instituicoes',['_ctx'=>$ctx,'unidade_id'=>self::UNIDADE,'edicao_id'=>self::EDICAO,'confirmacao'=>1])->assertRedirect()->assertSessionHas('erro','O catálogo mudou. Busque e confira novamente.');
    }
}
