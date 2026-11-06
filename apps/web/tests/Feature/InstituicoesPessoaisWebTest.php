<?php
namespace Tests\Feature;

use App\Support\{Acesso,ContextoWeb};
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class InstituicoesPessoaisWebTest extends TestCase
{
    use \Tests\Support\ContextoSelecionado;
    private const UNIDADE='11111111-1111-4111-8111-111111111111';
    private const EDICAO='22222222-2222-4222-8222-222222222222';
    private function preparar(string $perfil='professor_autonomo'): array {
        config(['services.api.base_url'=>'http://api.test/api','catalogo.interface_ativa'=>true]);Http::preventStrayRequests();
        $this->withSession(['auth_token'=>'identidade','auth_vinculos'=>true,'auth_nome'=>'Pessoa fictícia']);
        $this->withCookie(config('session.cookie'),session()->getId())->withCredentials();
        $me=['vinculo_id'=>1,'tipo_contexto'=>'vinculo','token'=>'contexto-pessoal','perfil'=>$perfil,'espaco'=>['id'=>1,'tipo'=>$perfil==='professor_autonomo'?'pessoal':'escola','nome'=>'Pessoal fictício'],'permissoes'=>['catalogo.consultar','catalogo.referencia_pessoal.gerir']];
        $this->selecionarContextoFixture($me);return [ContextoWeb::referencia($me),$me];
    }
    private function e(): array { return ['unidade_id'=>self::UNIDADE,'instituicao_id'=>'33333333-3333-4333-8333-333333333333','nome'=>'Escola <script>ficticia</script>','sigla'=>'EF','natureza'=>'escola','tipo'=>'escola','dependencia'=>'estadual','uf'=>'BA','municipio_nome'=>'Porto fictício','municipio_ibge'=>'2900001','namespace'=>'inep_escola','codigo'=>'29000001','situacao_fonte'=>'1','situacao_rotulo'=>'Em atividade na fonte','elegivel'=>true,'edicao_id'=>self::EDICAO,'ano'=>2025,'fonte'=>'basica']; }
    private function pag(): array { return ['atual'=>1,'ultima'=>2,'por_pagina'=>20,'total'=>21]; }
    public function test_fluxo_busca_confirmacao_lista_e_contexto_sem_injetar_escopo(): void {
        [$ctx,$me]=$this->preparar();
        Http::fake(function($r)use($me){
            // O seletor compartilhado consulta opções pela identidade, não pelo token contextual.
            if ($r->method()==='GET' && preg_match('~/auth/(vinculos|acessos-internos)$~',$r->url())) {
                $this->assertTrue($r->hasHeader('Authorization','Bearer '.session('auth_token')));
                return Http::response(['data'=>[]]);
            }

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
        $this->get('/app/minhas-instituicoes/buscar?uf=BA&municipio_ibge=2900001&tipo=escola')->assertOk()->assertSee('Conferir instituição')->assertSee('Próxima')->assertSee('INEP da escola')->assertDontSee('<script>ficticia</script>',false);
        $this->get('/app/minhas-instituicoes/conferir/'.self::UNIDADE.'?edicao_id='.self::EDICAO)->assertOk()->assertSee('não confirma vínculo profissional')->assertSee('name="confirmacao"',false)->assertPaginaSemSelecaoPrevia();
        $this->post('/app/minhas-instituicoes',['unidade_id'=>self::UNIDADE,'edicao_id'=>self::EDICAO,'confirmacao'=>1,'espaco_id'=>999])->assertRedirect();
        Http::assertSent(fn($r)=>$r->method()==='POST' && $r['unidade_id']===self::UNIDADE && !isset($r['espaco_id']) && !isset($r['_ctx']));
        $this->get('/app/minhas-instituicoes')->assertOk()->assertSee('Minhas instituições')->assertSee('Arquivar referência')->assertDontSee('href="'.route('escolas.index').'"',false);
    }
    public function test_busca_combinada_recupera_422_preservando_valores_sem_enviar_modo_ou_contexto_a_api(): void {
        [$ctx,$me]=$this->preparar();
        Http::fake(function($r)use($me){
            // O seletor compartilhado consulta opções pela identidade, não pelo token contextual.
            if ($r->method()==='GET' && preg_match('~/auth/(vinculos|acessos-internos)$~',$r->url())) {
                $this->assertTrue($r->hasHeader('Authorization','Bearer '.session('auth_token')));
                return Http::response(['data'=>[]]);
            }

            $path=parse_url($r->url(),PHP_URL_PATH);
            if($path==='/api/v1/auth/me')return Http::response(['data'=>$me]);
            if($path==='/api/v1/catalogo/ufs')return Http::response(['data'=>['itens'=>[['sigla'=>'BA','nome'=>'Bahia','codigo_ibge'=>29]]]]);
            return Http::response(['errors'=>['codigo'=>['Código incompatível com a fonte.']]],422);
        });
        $this->get('/app/minhas-instituicoes/buscar?'.http_build_query(['modo'=>'codigo','namespace'=>'inep_escola','codigo'=>'123','uf'=>'BA','tipo'=>'escola','busca'=>'Colégio fictício']))
            ->assertStatus(422)->assertSee('Confira os filtros para continuar')->assertSee('Código incompatível com a fonte.')
            ->assertSee('value="Colégio fictício"',false)->assertSee('value="123"',false)->assertSee('Restringir também por nome, localização ou tipo')
            ->assertDontSee('Nenhuma instituição corresponde');
        Http::assertSent(fn($r)=>str_contains($r->url(),'/catalogo/instituicoes') && $r['uf']==='BA' && $r['busca']==='Colégio fictício'
            && $r['codigo']==='123' && !isset($r['modo']) && !isset($r['_ctx']));
    }
    public function test_percurso_por_codigo_sem_criterios_nao_consulta_catalogo_e_array_de_filtro_e_recusado(): void {
        [$ctx,$me]=$this->preparar();
        Http::fake(fn($r)=>str_contains($r->url(),'/auth/me')?Http::response(['data'=>$me]):Http::response(['data'=>['itens'=>[['sigla'=>'BA','nome'=>'Bahia','codigo_ibge'=>29]]]]));
        $this->get('/app/minhas-instituicoes/buscar?modo=codigo')->assertOk()->assertSee('Qual é a origem do código?')->assertSee('Código da instituição');
        $this->get('/app/minhas-instituicoes/buscar?modo=nome&pesquisar=1')->assertStatus(422)->assertSee('Informe um nome, uma localização ou use a busca por código.');
        Http::assertNotSent(fn($r)=>str_contains($r->url(),'/catalogo/instituicoes'));
        $this->get('/app/minhas-instituicoes/buscar?'.http_build_query(['busca'=>['filtro-inválido']]))
            ->assertStatus(422)->assertSee('Informe um único valor para este filtro.');
        Http::assertNotSent(fn($r)=>str_contains($r->url(),'/catalogo/instituicoes'));
    }
    public function test_erro_api_e_rede_nao_sao_exibidos_como_resultado_vazio(): void {
        [$ctx,$me]=$this->preparar();
        Http::fake(fn($r)=>str_ends_with($r->url(),'/auth/me')?Http::response(['data'=>$me]):Http::response(['message'=>'SEGREDO-INTERNO'],500));
        $this->get('/app/minhas-instituicoes/buscar?uf=BA')->assertStatus(503)->assertDontSee('Nenhuma instituição encontrada')->assertDontSee('SEGREDO-INTERNO');
        Http::fake(fn($r)=>str_ends_with($r->url(),'/auth/me')?Http::response(['data'=>$me]):Http::failedConnection());
        $this->getJson('/app/minhas-instituicoes/municipios?uf=BA')->assertStatus(503);
    }
    public function test_flag_e_contexto_institucional_fecham_tela_e_menu(): void {
        [$ctx,$me]=$this->preparar('professor');Http::fake(['*'=>Http::response(['data'=>$me])]);
        $this->get('/app/minhas-instituicoes')->assertForbidden();
        Http::assertNotSent(fn($r)=>str_contains($r->url(),'/catalogo/'));
        config(['catalogo.interface_ativa'=>false]);$this->get('/app/minhas-instituicoes')->assertNotFound();
    }
    public function test_edicao_mudou_exige_nova_busca_sem_selecao_silenciosa(): void {
        [$ctx,$me]=$this->preparar();Http::fake(fn($r)=>str_ends_with($r->url(),'/auth/me')?Http::response(['data'=>$me]):Http::response(['message'=>'Edição mudou'],409));
        $this->post('/app/minhas-instituicoes',['unidade_id'=>self::UNIDADE,'edicao_id'=>self::EDICAO,'confirmacao'=>1])->assertRedirect()->assertSessionHas('erro','O catálogo mudou. Busque e confira novamente.');
    }
    public function test_somente_consulta_nao_oferece_acao_de_gravacao(): void {
        [$ctx,$me]=$this->preparar();$me['permissoes']=['catalogo.consultar'];
        Http::fake(fn($r)=>Http::response(['data'=>str_ends_with($r->url(),'/auth/me')?$me:(str_contains($r->url(),'/instituicoes/')?$this->e():['itens'=>[],'paginacao'=>['atual'=>1,'ultima'=>1,'por_pagina'=>20,'total'=>0]])]));
        $this->get('/app/minhas-instituicoes')->assertOk()->assertSee('Consultar catálogo')->assertDontSee('Adicionar instituição');
        $this->get('/app/minhas-instituicoes/conferir/'.self::UNIDADE.'?edicao_id='.self::EDICAO)->assertOk()->assertSee('Dependência administrativa: Estadual')->assertSee('Seu acesso permite consultar')->assertDontSee('name="confirmacao"',false);
    }
}
