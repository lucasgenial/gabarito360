<?php
namespace Tests\Feature;
use App\Support\ContextoWeb;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class SelecaoResultadosWebTest extends TestCase
{
    use \Tests\Support\ContextoSelecionado;
    private function preparar(array $permissoes=['copia.autorizar_origem']): array
    {
        config(['services.api.base_url'=>'http://api.test/api','services.copias.interface_ativa'=>true]); Http::preventStrayRequests();
        $this->withSession(['auth_token'=>'identidade','auth_vinculos'=>true,'auth_nome'=>'Fictício']);
        $this->withCookie(config('session.cookie'),session()->getId())->withCredentials();
        $me=['vinculo_id'=>7,'token'=>'contexto-resultados','tipo_contexto'=>'vinculo','perfil'=>'dir_escolar','espaco'=>['id'=>5,'tipo'=>'escola','nome'=>'Escola'],'permissoes'=>$permissoes];
        return [$this->selecionarContextoFixture($me),$me];
    }
    private function linha(): array
    { return ['id'=>1,'prova'=>'Prova <script>segredo</script>','aluno'=>'Aluno','matricula'=>'MAT-1','turma'=>'Turma','nota_final'=>'8.00']; }
    private function catalogo(): array
    { return ['espacos'=>[['id'=>5,'nome'=>'Escola']],'espaco_id'=>5,'lista'=>['data'=>[$this->linha()],'total'=>21,'current_page'=>1,'last_page'=>2]]; }
    private function previa(): array
    { return ['espaco_id'=>5,'tipo_destino'=>'pessoal','resultados'=>[$this->linha()+['dados'=>['nota_final'=>'8.00','acertos'=>8,'total_questoes'=>10,'status_aprovacao'=>'aprovado','lingua'=>'ING']]],
        'dependencias'=>['provas'=>[['id'=>2,'titulo'=>'Prova <script>segredo</script>']],'turmas'=>[['id'=>1,'nome'=>'Turma']],'alunos'=>[['id'=>1,'nome'=>'Aluno','matricula'=>'MAT-1']]]]; }
    public function test_selecao_contexto_csrf_escape_e_paginacao(): void
    {
        [$ctx,$me]=$this->preparar(); Http::fake(function($r) use($me) {
            // O seletor compartilhado consulta opções pela identidade, não pelo token contextual.
            if ($r->method()==='GET' && preg_match('~/auth/(vinculos|acessos-internos)$~',$r->url())) {
                $this->assertTrue($r->hasHeader('Authorization','Bearer '.session('auth_token')));
                return Http::response(['data'=>[]]);
            }

            $this->assertTrue($r->hasHeader('Authorization','Bearer contexto-resultados'));
            return Http::response(['data'=>str_ends_with($r->url(),'/auth/me')?$me:$this->catalogo()]);
        });
        $this->get('/app/copias/resultados')->assertOk()->assertSee('name="notas[]"',false)->assertSee('name="_token"',false)
            ->assertDontSee($ctx)->assertDontSee('contexto-cadastro')->assertDontSee('contexto-copia')->assertSee('Próxima')->assertPaginaSemSelecaoPrevia()->assertDontSee('<script>segredo</script>',false);
    }
    public function test_previa_nao_comprova_historico_nem_confirma_dependencias(): void
    {
        [$ctx,$me]=$this->preparar(); Http::fake(fn($r)=>Http::response(['data'=>str_ends_with($r->url(),'/auth/me')?$me:$this->previa()]));
        $this->post('/app/copias/resultados/previa',['espaco_id'=>5,'tipo_destino'=>'pessoal','notas'=>[1],'ator_id'=>99,'dependencias'=>['alunos'=>[9]]])
            ->assertOk()->assertSee('A versão histórica original não está comprovada')->assertSee('não foi recalculada')->assertSee('8.00')
            ->assertSee('Dependências da seleção')->assertSee('não equivale a confirmar sua transferência')->assertSee('guarda contratual')
            ->assertDontSee('<script>segredo</script>',false)->assertDontSee('Autorizar saída')->assertDontSee('Registrar evidência');
        Http::assertSent(fn($r)=>$r->method()==='POST' && $r['notas']===[1] && !isset($r['ator_id']) && !isset($r['dependencias']) && !isset($r['_ctx']));
        Http::assertNotSent(fn($r)=>str_contains($r->url(),'/lotes') || str_contains($r->url(),'/aprovar'));
        $this->assertNull(session('_old_input'));
    }
    public function test_erro_nao_expoe_dados_nem_grava_selecao_no_flash(): void
    {
        [$ctx,$me]=$this->preparar(); Http::fake(fn($r)=>str_ends_with($r->url(),'/auth/me')?Http::response(['data'=>$me]):Http::response(['message'=>'ALUNO-PRIVADO'],422));
        $this->post('/app/copias/resultados/previa',['espaco_id'=>5,'tipo_destino'=>'pessoal'])->assertStatus(422);
        $this->post('/app/copias/resultados/previa',['espaco_id'=>5,'tipo_destino'=>'pessoal','notas'=>[1]])->assertStatus(422)->assertDontSee('ALUNO-PRIVADO');
        $this->assertNull(session('_old_input')); $this->assertStringNotContainsString('ALUNO-PRIVADO',json_encode(session()->all()));
    }
    public function test_capacidade_destino_e_flag_fecham_tela(): void
    {
        [$ctx,$me]=$this->preparar(['copia.autorizar_destino']); Http::fake(['*'=>Http::response(['data'=>$me])]);
        $this->get('/app/copias/resultados')->assertForbidden(); config(['services.copias.interface_ativa'=>false]);
        $this->get('/app/copias/resultados')->assertNotFound(); Http::assertNotSent(fn($r)=>str_contains($r->url(),'/copias'));
    }
    public function test_conexao_indisponivel_nao_vira_resultado_vazio(): void
    {
        [$ctx,$me]=$this->preparar(); Http::fake(function($r) use($me) {
            // O seletor compartilhado consulta opções pela identidade, não pelo token contextual.
            if ($r->method()==='GET' && preg_match('~/auth/(vinculos|acessos-internos)$~',$r->url())) {
                $this->assertTrue($r->hasHeader('Authorization','Bearer '.session('auth_token')));
                return Http::response(['data'=>[]]);
            }

            if(str_ends_with($r->url(),'/auth/me')) return Http::response(['data'=>$me]);
            throw new \Illuminate\Http\Client\ConnectionException('ALUNO-PRIVADO');
        });
        $this->get('/app/copias/resultados')->assertStatus(503)->assertDontSee('ALUNO-PRIVADO');
    }
}
