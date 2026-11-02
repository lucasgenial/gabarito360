<?php
namespace Tests\Feature;

use App\Support\ContextoWeb;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class ClientesWebTest extends TestCase
{
    private function preparar(string $perfil='financeiro', bool $interno=true): array
    {
        config(['services.api.base_url'=>'http://api.test/api']);
        Http::preventStrayRequests();
        $this->withSession(['auth_token'=>'identidade','auth_vinculos'=>true,'auth_nome'=>'Operador fictício']);
        $this->withCookie(config('session.cookie'),session()->getId())->withCredentials();
        $dados=['token'=>'comercial-token','tipo_contexto'=>$interno ? 'interno' : 'vinculo','perfil'=>$perfil,'permissoes'=>['cliente.ver','cliente.gerir']];
        return [ContextoWeb::criar($dados),$dados];
    }
    private function pagina(array $linhas): array { return ['data'=>$linhas,'total'=>count($linhas),'current_page'=>1,'last_page'=>1]; }

    public function test_menu_formulario_edicao_e_espacos_preservam_contexto_comercial(): void
    {
        [$id,$dados]=$this->preparar();
        $cliente=['id'=>8,'nome'=>'Cliente fictício','tipo'=>'escola','modalidade'=>'teste','situacao'=>'ativo','contato_email'=>'teste@example.test'];
        Http::fake(function ($r) use ($dados,$cliente) {
            $this->assertTrue($r->hasHeader('Authorization','Bearer comercial-token'));
            $path=parse_url($r->url(),PHP_URL_PATH);
            if ($path==='/api/v1/auth/me') return Http::response(['data'=>$dados]);
            if (in_array($r->method(),['POST','PUT'],true)) return Http::response(['data'=>['id'=>8]],$r->method()==='POST' ? 201 : 200);
            return match ($path) {
                '/api/v1/clientes'=>Http::response(['data'=>$this->pagina([$cliente])]),
                '/api/v1/clientes/8'=>Http::response(['data'=>$cliente]),
                '/api/v1/clientes/8/espacos'=>Http::response(['data'=>$this->pagina([['id'=>1,'nome'=>'Espaço fictício','tipo'=>'escola','situacao'=>'ativo','vinculos'=>['ativo'=>2]]])]),
                default=>$this->fail('Consulta inesperada: '.$path),
            };
        });
        $this->get('/painel?_ctx='.$id)->assertOk()->assertSee('Gerenciar clientes')->assertDontSee('Acessos internos');
        $this->get('/clientes?_ctx='.$id)->assertOk()->assertSee('Cliente fictício')->assertDontSee('teste@example.test')->assertDontSee('comercial-token');
        $this->get('/clientes/novo?_ctx='.$id)->assertOk()->assertSee('Referência contratual')->assertSee('Salvar cadastro');
        $this->post('/clientes',['_ctx'=>$id,'nome'=>'Cliente fictício','tipo'=>'escola','modalidade'=>'teste','situacao'=>'suspenso'])
            ->assertRedirect(route('clientes.show',8).'?_ctx='.$id)->assertSessionHas('sucesso');
        Http::assertSent(fn($r)=>$r->method()==='POST' && $r['nome']==='Cliente fictício' && !isset($r['situacao']) && !isset($r['_ctx']));
        $this->get('/clientes/8?_ctx='.$id)->assertOk()->assertSee('teste@example.test')->assertSee('Espaço fictício')->assertSee('Ativo: 2');
        $this->put('/clientes/8',['_ctx'=>$id,'nome'=>'Cliente editado','modalidade'=>'presenteado'])->assertRedirect()->assertSessionHas('sucesso');
        Http::assertNotSent(fn($r)=>str_contains($r->url(),'/usuarios') || str_contains($r->url(),'/alunos'));
    }
    public function test_papel_escolar_nao_ganha_acesso_mesmo_com_permissao_injetada(): void
    {
        [$id,$dados]=$this->preparar('dir_escolar',false);
        Http::fake(['*'=>Http::response(['data'=>$dados])]);
        foreach (['/clientes','/clientes/novo','/clientes/8'] as $url) $this->get($url.'?_ctx='.$id)->assertForbidden();
        $this->post('/clientes',['_ctx'=>$id])->assertForbidden();
        Http::assertNotSent(fn($r)=>str_contains($r->url(),'/v1/clientes'));
    }
    public function test_erro_de_validacao_preserva_formulario_sem_expor_resposta_interna(): void
    {
        [$id,$dados]=$this->preparar();
        Http::fake(fn($r)=>str_ends_with($r->url(),'/auth/me') ? Http::response(['data'=>$dados]) : Http::response(['message'=>'SEGREDO-INTERNO'],422));
        $this->from('/clientes/novo')->post('/clientes',['_ctx'=>$id,'nome'=>'Teste','contato_email'=>'invalido'])->assertRedirect()->assertSessionHas('erro')->assertSessionHasInput('nome','Teste');
        $this->assertStringNotContainsString('SEGREDO-INTERNO',session('erro'));
    }
}
