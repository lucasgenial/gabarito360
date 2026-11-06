<?php
namespace Tests\Feature;

use App\Services\ApiClient;
use App\Support\ContextoWeb;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

final class ContratoApiIndependenteTest extends TestCase
{
    public function test_outro_host_e_prefixo_preservam_metodo_versao_filtros_json_e_bearer_no_cabecalho(): void
    {
        config(['services.api.base_url'=>'https://api-independente.example.test/servico/api/']);
        $this->withSession(['auth_token'=>'identidade-ficticia','auth_vinculos'=>true]);
        ContextoWeb::ativar(['tipo_contexto'=>'vinculo','vinculo_id'=>7,'token'=>'vinculo-ficticio','perfil'=>'coordenador','permissoes'=>[]]);
        ContextoWeb::capturar(request());
        Http::preventStrayRequests();Http::fake(['*'=>Http::response(['data'=>['kpis'=>['provas_andamento'=>0],'provas'=>[]]],200)]);
        $r=app(ApiClient::class)->get('/v1/dashboard/coordenador',['escola_id'=>13]);
        $this->assertSame(['kpis'=>['provas_andamento'=>0],'provas'=>[]],$r->json('data'));
        Http::assertSent(function($r){
            $this->assertSame('GET',$r->method());$this->assertSame('api-independente.example.test',parse_url($r->url(),PHP_URL_HOST));
            $this->assertSame('/servico/api/v1/dashboard/coordenador',parse_url($r->url(),PHP_URL_PATH));
            parse_str(parse_url($r->url(),PHP_URL_QUERY)??'',$query);$this->assertSame(['escola_id'=>'13'],$query);
            return $r->hasHeader('Authorization','Bearer vinculo-ficticio') && $r->hasHeader('Accept','application/json');
        });Http::assertSentCount(1);
    }

    public function test_identidade_login_e_erros_preservam_o_contrato_sem_depender_das_rotas_web(): void
    {
        config(['services.api.base_url'=>'http://127.0.0.1:18081/api']);
        $this->withSession(['auth_token'=>'identidade-ficticia']);ContextoWeb::capturar(request());
        Http::preventStrayRequests();Http::fake(['*'=>Http::response(['message'=>'Acesso negado.'],403)]);
        $r=app(ApiClient::class)->identidade()->post('/v1/auth/vinculos/selecionar',['vinculo_id'=>7]);
        $this->assertSame(403,$r->status());$this->assertSame(['message'=>'Acesso negado.'],$r->json());
        Http::assertSent(fn($r)=>$r->method()==='POST' && parse_url($r->url(),PHP_URL_PATH)==='/api/v1/auth/vinculos/selecionar'
            &&parse_url($r->url(),PHP_URL_QUERY)===null && $r->hasHeader('Authorization','Bearer identidade-ficticia') && $r->data()===['vinculo_id'=>7]);
        Http::assertSentCount(1);
    }

    public function test_configuracao_padrao_nao_duplica_v1_nem_envia_credenciais_a_host_publico(): void
    {
        $antesEnv=$_ENV['API_BASE_URL']??null;$antesServer=$_SERVER['API_BASE_URL']??null;$antesProcesso=getenv('API_BASE_URL');
        unset($_ENV['API_BASE_URL'],$_SERVER['API_BASE_URL']);putenv('API_BASE_URL');
        try {
            $servicos=require config_path('services.php');config(['services.api.base_url'=>$servicos['api']['base_url']]);
            Http::preventStrayRequests();Http::fake(['*'=>Http::response(['data'=>['status'=>'ok']],200)]);
            app(ApiClient::class)->get('/v1/health');
            Http::assertSent(fn($r)=>$r->url()==='http://127.0.0.1:8001/api/v1/health' && $r->method()==='GET');Http::assertSentCount(1);
        } finally {
            if($antesEnv!==null)$_ENV['API_BASE_URL']=$antesEnv;if($antesServer!==null)$_SERVER['API_BASE_URL']=$antesServer;
            if($antesProcesso!==false)putenv('API_BASE_URL='.$antesProcesso);
        }
    }
}
