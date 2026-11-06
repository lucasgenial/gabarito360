<?php
namespace Tests\Feature;

use App\Support\{ContextoWeb,NavegacaoWeb};
use Illuminate\Support\Facades\Http;
use Tests\Support\AcessoAtivoLegado;
use Tests\TestCase;

final class RecursosCorrecaoWebTest extends TestCase
{
    use AcessoAtivoLegado;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withSession(['auth_token'=>'ficticio','auth_perfil'=>'coordenador','auth_nome'=>'Pessoa fictícia','auth_vinculos'=>false]);
        $this->selecionarAcessoLegado('coordenador',['correcao.acompanhar','correcao.alterar','resultado.ver','cartao.enviar']);
        Http::preventStrayRequests();
    }

    public function test_cartoes_do_bff_descrevem_acoes_e_fotos_sem_id_na_url_e_preservam_api(): void
    {
        $this->apiLegada(['api.test/api/v1/provas/4/cartoes'=>Http::response(['data'=>['cartoes'=>[['id'=>65,'aluno_id'=>1,'acertos'=>3,'foto_url'=>'/app/cartoes/65/imagens/original','miniatura_url'=>'/app/cartoes/65/imagens/miniatura']]]])]);
        $d=NavegacaoWeb::destino('correcao.cartoes',[4]);
        $r=$this->postJson($d['url'],['_recurso'=>$d['referencia'],'_revisao'=>ContextoWeb::revisao()])->assertOk();
        foreach (['retirada','excluir','vincular-aluno','revisar','resolver-ambiguidade','imagem','revisao','resultado'] as $acao) {
            $recurso=$r->json('cartoes.0._recursos.'.$acao);$this->assertIsArray($recurso);$this->assertStringNotContainsString('/65',$recurso['url']);$this->assertStringNotContainsString('?', $recurso['url']);
            $resolvido=NavegacaoWeb::obter($recurso['referencia']);$this->assertNotNull($resolvido);
        }
        foreach (['foto_url','miniatura_url'] as $campo) $this->assertSame(65,NavegacaoWeb::obter($r->json('cartoes.0.'.$campo.'.referencia'))['parametros']['id']);
        Http::assertSent(fn($q)=>$q->method()==='GET'&&$q->url()==='http://api.test/api/v1/provas/4/cartoes'&&!isset($q['_recurso'],$q['_revisao']));
    }

    public function test_envio_consultado_usa_chave_no_corpo_e_devolve_navegacao_da_tentativa(): void
    {
        $chave='f4951e0c-7470-42f8-8141-683c25514b7e';$tentativa='99999999-9999-4999-8999-999999999999';
        $this->apiLegada(['api.test/api/v1/provas/4/cartoes/envios/'.$chave=>Http::response(['data'=>['processamento'=>'aguardando_confirmacao','resultado'=>['tentativa_id'=>$tentativa]]])]);
        $d=NavegacaoWeb::destino('correcao.envio',['id'=>4]);
        $r=$this->postJson($d['url'],['_recurso'=>$d['referencia'],'_revisao'=>ContextoWeb::revisao(),'chave'=>$chave])->assertOk();
        $ref=$r->json('resultado._recursos.tentativa.referencia');$this->assertSame($tentativa,NavegacaoWeb::obter($ref)['parametros']['id']);
        $this->assertStringNotContainsString($tentativa,$r->json('resultado._recursos.tentativa.url'));
        Http::assertSent(fn($q)=>$q->method()==='GET'&&str_ends_with($q->url(),'/provas/4/cartoes/envios/'.$chave));
    }

    public function test_recusa_da_api_e_preservada_sem_inventar_acoes_de_recurso(): void
    {
        $this->apiLegada(['api.test/api/v1/provas/4/cartoes'=>Http::response(['message'=>'Indisponível.'],403)]);
        $d=NavegacaoWeb::destino('correcao.cartoes',[4]);
        $this->postJson($d['url'],['_recurso'=>$d['referencia'],'_revisao'=>ContextoWeb::revisao()])->assertForbidden()->assertExactJson(['cartoes'=>[]]);
    }
    public function test_telas_de_correcao_e_revisao_carregam_configuracao_nao_executavel_e_js_externo(): void
    {
        $prova=['id'=>4,'titulo'=>'Prova fictícia','disciplina'=>'Teste','data_aplicacao'=>null,'num_alternativas'=>5,'nota_maxima'=>10,'turmas'=>[],'status'=>'publicada'];
        $this->apiLegada([
            'api.test/api/v1/provas/4'=>Http::response(['data'=>$prova]),
            'api.test/api/v1/provas/4/revisao'=>Http::response(['data'=>['itens'=>[['cartao_id'=>65,'numero_questao'=>1,'foto_url'=>'/app/cartoes/65/imagens/original','endireitada_url'=>'/app/cartoes/65/imagens/endireitada']]]]),
        ]);
        foreach (['correcao.show'=>'correcao-acompanhamento','correcao.revisao'=>'correcao-revisao'] as $rota=>$js) {
            $d=NavegacaoWeb::destino($rota,[4]);
            $r=$this->withHeader('X-G360-Hidratar','1')->post($d['url'],['_recurso'=>$d['referencia'],'_revisao'=>ContextoWeb::revisao()])->assertOk();
            $r->assertSee($js.'.js')->assertDontSee('var CARTOES_URL',false);
            $dom=new \DOMDocument();@$dom->loadHTML($r->getContent());$xp=new \DOMXPath($dom);$cfg=$xp->query('//script[@id="'.$js.'-config"]')->item(0);
            $this->assertNotNull($cfg);$this->assertSame('application/json',$cfg->getAttribute('type'));$config=json_decode($cfg->textContent,true,512,JSON_THROW_ON_ERROR);$this->assertIsArray($config);
            if($rota==='correcao.revisao')$this->assertSame(65,NavegacaoWeb::obter($config['itens'][0]['foto_url']['referencia'])['parametros']['id']);
        }
    }

}
