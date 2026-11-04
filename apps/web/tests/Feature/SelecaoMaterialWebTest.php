<?php
namespace Tests\Feature;
use App\Support\ContextoWeb;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class SelecaoMaterialWebTest extends TestCase
{
    private function preparar(array $permissoes=['copia.autorizar_origem']): array
    {
        config(['services.api.base_url'=>'http://api.test/api','services.copias.interface_ativa'=>true]); Http::preventStrayRequests();
        $this->withSession(['auth_token'=>'identidade','auth_vinculos'=>true,'auth_nome'=>'Fictício']);
        $this->withCookie(config('session.cookie'),session()->getId())->withCredentials();
        $me=['token'=>'contexto-material','tipo_contexto'=>'vinculo','perfil'=>'dir_escolar','espaco'=>['id'=>5,'tipo'=>'escola','nome'=>'Escola fictícia'],'permissoes'=>$permissoes];
        return [ContextoWeb::criar($me),$me];
    }
    private function catalogo(): array
    {
        return ['espacos'=>[['id'=>5,'nome'=>'Escola fictícia']],'espaco_id'=>5,
            'lista'=>['data'=>[['id'=>2,'titulo'=>'Prova <script>segredo</script>','disciplina'=>'Matemática','serie'=>'3','num_questoes'=>1]],'total'=>21,'current_page'=>1,'last_page'=>2]];
    }
    private function previa(): array
    {
        return ['espaco_id'=>5,'autoriza_execucao'=>false,'revisao_obrigatoria'=>true,'materiais'=>[['origem_id'=>2,'material'=>[
            'prova'=>['titulo'=>'Prova <script>segredo</script>','disciplina'=>'Matemática','serie'=>'3','num_questoes'=>1,'num_alternativas'=>4,
                'nota_maxima'=>'10.00','tipo_pontuacao'=>'igual','anular_se_todas'=>false,'opcao_lingua'=>false],
            'questoes'=>[['numero_questao'=>1,'alternativa'=>'A','alternativa_espanhol'=>null,'peso'=>'1.00','anulada'=>false]]]]]];
    }
    public function test_selecao_post_previa_e_contexto_sem_autorizacao(): void
    {
        [$ctx,$me]=$this->preparar();
        Http::fake(function($r) use($me) {
            // O seletor compartilhado consulta opções pela identidade, não pelo token contextual.
            if ($r->method()==='GET' && preg_match('~/auth/(vinculos|acessos-internos)$~',$r->url())) {
                $this->assertTrue($r->hasHeader('Authorization','Bearer '.session('auth_token')));
                return Http::response(['data'=>[]]);
            }

            $this->assertTrue($r->hasHeader('Authorization','Bearer contexto-material'));
            return Http::response(['data'=>str_ends_with($r->url(),'/auth/me')?$me:($r->method()==='POST'?$this->previa():$this->catalogo())]);
        });
        $this->get('/copias/materiais?_ctx='.$ctx)->assertOk()->assertSee('Conferir prévia')->assertSee('name="provas[]"',false)
            ->assertSee('name="_token"',false)->assertSee($ctx)->assertDontSee('checked',false)->assertDontSee('<script>segredo</script>',false);
        $this->post('/copias/materiais/previa',['_ctx'=>$ctx,'espaco_id'=>5,'provas'=>[2],'destino_espaco_id'=>7,'vinculo_id'=>4])
            ->assertOk()->assertSee('Esta consulta não autoriza nem inicia uma cópia')->assertSee('Conferir gabarito')
            ->assertSee('10.00')->assertDontSee('<script>segredo</script>',false)->assertDontSee('Aprovar cópia');
        Http::assertSent(fn($r)=>$r->method()==='POST' && $r['provas']===[2] && !isset($r['_ctx']) && !isset($r['destino_espaco_id']) && !isset($r['vinculo_id']));
    }
    public function test_erro_de_gabarito_na_propria_selecao_sem_mensagem_interna(): void
    {
        [$ctx,$me]=$this->preparar();
        Http::fake(fn($r)=>$r->method()==='POST'?Http::response(['message'=>'SEGREDO-INTERNO'],422):Http::response(['data'=>str_ends_with($r->url(),'/auth/me')?$me:$this->catalogo()]));
        $this->post('/copias/materiais/previa',['_ctx'=>$ctx,'espaco_id'=>5,'provas'=>[2]])->assertStatus(422)
            ->assertSee('Confira a seleção')->assertSee('Conferir prévia')->assertDontSee('SEGREDO-INTERNO');
    }
    public function test_capacidade_destino_nao_libera_origem_e_flag_fecha_tela(): void
    {
        [$ctx,$me]=$this->preparar(['copia.autorizar_destino']); Http::fake(['*'=>Http::response(['data'=>$me])]);
        $this->get('/copias/materiais?_ctx='.$ctx)->assertForbidden();
        Http::assertNotSent(fn($r)=>str_contains($r->url(),'/copias/materiais'));
        config(['services.copias.interface_ativa'=>false]); $this->get('/copias/materiais?_ctx='.$ctx)->assertNotFound();
    }
    public function test_falha_de_conexao_nao_vira_previa_ou_vaza_dados(): void
    {
        [$ctx,$me]=$this->preparar();
        Http::fake(function($r) use($me) {
            // O seletor compartilhado consulta opções pela identidade, não pelo token contextual.
            if ($r->method()==='GET' && preg_match('~/auth/(vinculos|acessos-internos)$~',$r->url())) {
                $this->assertTrue($r->hasHeader('Authorization','Bearer '.session('auth_token')));
                return Http::response(['data'=>[]]);
            }

            if (str_ends_with($r->url(),'/auth/me')) return Http::response(['data'=>$me]);
            throw new \Illuminate\Http\Client\ConnectionException('SEGREDO-CONEXAO');
        });
        $this->post('/copias/materiais/previa',['_ctx'=>$ctx,'espaco_id'=>5,'provas'=>[2]])->assertStatus(503)->assertDontSee('SEGREDO-CONEXAO');
    }
}
