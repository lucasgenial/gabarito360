<?php
namespace Tests\Feature;

use Illuminate\Support\Facades\Http;
use Tests\TestCase;
use Tests\Support\AcessoAtivoLegado;

class SiglaInstituicaoWebTest extends TestCase
{
    use AcessoAtivoLegado;

    private function preparar(): void
    {
        config(['services.api.base_url'=>'http://api.test/api']); Http::preventStrayRequests();
        $this->withSession(['auth_token'=>'ficticio','auth_nome'=>'Gestor','auth_perfil'=>'admin_rede','auth_permissoes'=>['escola.criar','escola.editar','nucleo.ver']]);
        $this->selecionarAcessoLegado('admin_rede', ['escola.criar', 'escola.editar', 'nucleo.ver']);
        $this->apiLegada(function($r) {
            $path=parse_url($r->url(),PHP_URL_PATH);
            if($r->method()!=='GET') return Http::response(['data'=>['id'=>1]],201);
            return match($path) {
                '/api/v1/escolas/1'=>Http::response(['data'=>['id'=>1,'nome'=>'Escola fictícia','sigla'=>'CE"<script>','inep'=>'29000001','nucleo_id'=>1,'tipo_rede'=>'estadual']]),
                '/api/v1/nucleos'=>Http::response(['data'=>['nucleos'=>[]]]),
                '/api/v1/escolas'=>Http::response(['data'=>['escolas'=>[]]]),
                default=>$this->fail('Consulta inesperada: '.$path),
            };
        });
    }

    public function test_formulario_edita_sigla_opcional_com_escape(): void
    {
        $this->preparar();
        $this->get('/app/escolas/1/editar')->assertOk()->assertSee('Sigla (opcional)')->assertSee('name="sigla"',false)->assertSee('CE&quot;&lt;script&gt;',false);
    }

    public function test_sigla_chega_na_api_sem_substituir_nome_completo(): void
    {
        $this->preparar();
        $this->put('/app/escolas/1',['nome'=>'Escola fictícia','sigla'=>'  CE-CAMPO  '])->assertRedirect()->assertSessionHas('sucesso');
        Http::assertSent(fn($r)=>$r->method()==='PUT' && $r['sigla']==='CE-CAMPO' && $r['nome']==='Escola fictícia');
    }
}
