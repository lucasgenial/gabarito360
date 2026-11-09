<?php
namespace Tests\Feature;

use App\Support\{ContextoWeb,NavegacaoWeb};
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/** O cache de compilação é estrutura da rota, nunca autoridade ou seleção de recurso. */
final class NavegacaoCatalogoRequisicaoWebTest extends TestCase
{
    public function test_catalogo_em_memoria_da_requisicao_nao_guarda_alvo_query_identidade_ou_referencia(): void
    {
        $this->withSession(['auth_token'=>'identidade-catalogo-secreta-ficticia','auth_vinculos'=>true]);
        $rev=ContextoWeb::ativar(['vinculo_id'=>7,'tipo_contexto'=>'vinculo','token'=>'token-contexto-secreto-ficticio',
            'perfil'=>'coordenador','permissoes'=>[],'espaco'=>['id'=>13,'tipo'=>'escola']]);
        Http::preventStrayRequests();
        $this->assertFalse(request()->attributes->has('ur4_catalogo_urls'));
        $a=NavegacaoWeb::deUrl('/app/turmas/927463185?busca=consulta-privada-ficticia','GET',true);
        $this->assertNotNull($a);
        $cat=request()->attributes->get('ur4_catalogo_urls');
        $this->assertSame(['fixos','originais','segmentos','curingas'],array_keys($cat));
        $this->assertNotEmpty($cat['originais']);
        foreach($cat['originais'] as $entrada){
            $this->assertSame(['meta','regex'],array_keys($entrada));$this->assertIsArray($entrada['meta']);
            $this->assertIsString($entrada['regex']);
            foreach(['token','identidade','revisao','consulta_valores','alvo','referencia'] as $chave)
                $this->assertArrayNotHasKey($chave,$entrada['meta']);
        }
        $json=json_encode($cat,JSON_THROW_ON_ERROR);
        foreach(['927463185','consulta-privada-ficticia','identidade-catalogo-secreta-ficticia',
            'token-contexto-secreto-ficticio',$rev,$a['referencia'],session('auth_contexto_ativo')] as $segredo)
            $this->assertStringNotContainsString($segredo,$json);
        $b=NavegacaoWeb::deUrl('/app/turmas/927463186?busca=outra-consulta-ficticia','GET',true);
        $this->assertSame($cat,request()->attributes->get('ur4_catalogo_urls'));
        $this->assertNotSame($a['referencia'],$b['referencia']);
        $this->assertSame('927463185',NavegacaoWeb::obter($a['referencia'])['parametros']['id']);
        $this->assertSame('927463186',NavegacaoWeb::obter($b['referencia'])['parametros']['id']);
        Http::assertNothingSent();
    }

    public function test_catalogo_compilado_no_cache_e_cifrado_e_adulteracao_e_ignorada(): void
    {
        $this->withSession(['auth_token'=>'identidade-ficticia','auth_vinculos'=>true]);
        ContextoWeb::ativar(['vinculo_id'=>7,'tipo_contexto'=>'vinculo','token'=>'ficticio','perfil'=>'professor',
            'permissoes'=>[],'espaco'=>['id'=>13,'tipo'=>'escola']]);
        Http::preventStrayRequests();
        $gravados=[];
        \Illuminate\Support\Facades\Event::listen(\Illuminate\Cache\Events\KeyWritten::class,function($e)use(&$gravados){if(str_starts_with($e->key,'ur4-catalogo:'))$gravados[$e->key]=$e->value;});
        NavegacaoWeb::deUrl('/app/turmas/14','GET',true);
        $this->assertCount(1,$gravados);
        [$chave,$valor]=[array_key_first($gravados),reset($gravados)];
        $this->assertIsString($valor);$this->assertStringNotContainsString('turmas',$valor,'o cache não guarda as expressões em claro');
        // Um catálogo em claro que casaria qualquer caminho com a primeira operação não é aceito.
        $total=count(request()->attributes->get('ur4_catalogo_urls')['originais']);
        foreach ([array_fill(0,$total,'#^.*$#'),'nao-cifrado',\Illuminate\Support\Facades\Crypt::encryptString(json_encode(array_fill(0,$total,'#^.*$#')).'x')] as $adulterado) {
            \Illuminate\Support\Facades\Cache::forever($chave,$adulterado);
            request()->attributes->remove('ur4_catalogo_urls');request()->attributes->remove('ur4_urls');
            $d=NavegacaoWeb::deUrl('/app/turmas/15','GET',true);
            $this->assertSame('turmas.show',NavegacaoWeb::obter($d['referencia'])['nome']);
            $this->assertSame('15',NavegacaoWeb::obter($d['referencia'])['parametros']['id']);
        }
        Http::assertNothingSent();
    }

    public function test_nova_requisicao_nao_reutiliza_catalogo_adulterado_da_anterior(): void
    {
        $this->withSession(['auth_token'=>'identidade-ficticia','auth_vinculos'=>true]);
        ContextoWeb::ativar(['vinculo_id'=>7,'tipo_contexto'=>'vinculo','token'=>'ficticio','perfil'=>'professor',
            'permissoes'=>[],'espaco'=>['id'=>13,'tipo'=>'escola']]);
        Http::preventStrayRequests();$antiga=request();
        NavegacaoWeb::deUrl('/app/turmas/14','GET',true);
        $antiga->attributes->set('ur4_catalogo_urls',['fixos'=>['/app/turmas/14'=>true],'originais'=>[]]);
        $nova=Request::create('/app/painel','GET');$nova->setLaravelSession(session()->driver());
        app()->instance('request',$nova);
        try{
            $this->assertFalse($nova->attributes->has('ur4_catalogo_urls'));
            $d=NavegacaoWeb::deUrl('/app/turmas/14','GET',true);$this->assertNotNull($d);
            $this->assertSame('turmas.show',NavegacaoWeb::obter($d['referencia'])['nome']);
            $this->assertArrayNotHasKey('/app/turmas/14',$nova->attributes->get('ur4_catalogo_urls')['fixos']);
        }finally{app()->instance('request',$antiga);}
        Http::assertNothingSent();
    }
}
