<?php
namespace Tests\Feature;
use App\Support\ContextoWeb;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class SelecaoCadastroWebTest extends TestCase
{
    private function preparar(array $permissoes=['copia.autorizar_origem']): array
    {
        config(['services.api.base_url'=>'http://api.test/api','services.copias.interface_ativa'=>true]); Http::preventStrayRequests();
        $this->withSession(['auth_token'=>'identidade','auth_vinculos'=>true,'auth_nome'=>'Fictício']);
        $this->withCookie(config('session.cookie'),session()->getId())->withCredentials();
        $me=['token'=>'contexto-cadastro','tipo_contexto'=>'vinculo','perfil'=>'dir_escolar','espaco'=>['id'=>5,'tipo'=>'escola','nome'=>'Escola'],'permissoes'=>$permissoes];
        return [ContextoWeb::criar($me),$me];
    }
    private function catalogo(): array
    {
        $t=['id'=>1,'nome'=>'Turma <script>segredo</script>','serie'=>'3','ano_letivo'=>2026];
        return ['espacos'=>[['id'=>5,'nome'=>'Escola']],'espaco_id'=>5,'turma'=>$t,
            'turmas'=>['data'=>[$t],'total'=>21,'current_page'=>1,'last_page'=>2],
            'alunos'=>['data'=>[['id'=>2,'nome'=>'Aluno <script>segredo</script>','matricula'=>'MAT-2']],'total'=>21,'current_page'=>1,'last_page'=>2]];
    }
    private function previa(string $tipo='pessoal'): array
    {
        return ['origem_espaco_id'=>5,'tipo_destino'=>$tipo,'pendencias'=>$tipo==='escola'?[['campo'=>'data_nascimento']]:[],
            'turmas'=>[['origem_id'=>1,'dados'=>['nome'=>'Turma','serie'=>'3','ano_letivo'=>2026,'turno'=>'manha','ativo'=>true]]],
            'alunos'=>[['origem_id'=>2,'dados'=>['nome'=>'Aluno <script>segredo</script>','matricula'=>'MAT-2','avatar_id'=>5,'ativo'=>true]]]];
    }
    public function test_catalogo_paginacao_dependencia_explicita_contexto_e_escape(): void
    {
        [$ctx,$me]=$this->preparar();
        Http::fake(function($r) use($me) {
            // O seletor compartilhado consulta opções pela identidade, não pelo token contextual.
            if ($r->method()==='GET' && preg_match('~/auth/(vinculos|acessos-internos)$~',$r->url())) {
                $this->assertTrue($r->hasHeader('Authorization','Bearer '.session('auth_token')));
                return Http::response(['data'=>[]]);
            }
 $this->assertTrue($r->hasHeader('Authorization','Bearer contexto-cadastro'));
            return Http::response(['data'=>str_ends_with($r->url(),'/auth/me')?$me:$this->catalogo()]); });
        $this->get('/copias/cadastros?espaco_id=5&turma_id=1&_ctx='.$ctx)->assertOk()
            ->assertSee('name="turmas[]"',false)->assertSee('name="alunos[]"',false)->assertSee('name="_token"',false)->assertSee($ctx)
            ->assertSee('Próximas turmas')->assertSee('Próximos alunos')->assertSee('não identifica um destinatário real')
            ->assertDontSee('checked',false)->assertDontSee('<script>segredo</script>',false);
    }
    public function test_previa_simulada_com_pendencia_e_sem_acoes_de_autorizacao(): void
    {
        [$ctx,$me]=$this->preparar();
        Http::fake(fn($r)=>Http::response(['data'=>str_ends_with($r->url(),'/auth/vinculos')?[]:(str_ends_with($r->url(),'/auth/me')?$me:$this->previa($r['tipo_destino']))]));
        $d=['_ctx'=>$ctx,'espaco_id'=>5,'tipo_destino'=>'pessoal','turmas'=>[1],'alunos'=>[2],'ator_id'=>99,'destino_espaco_id'=>7];
        $this->post('/copias/cadastros/previa',$d)->assertOk()->assertSee('Simulação para destino pessoal')->assertSee('MAT-2')
            ->assertDontSee('Nascimento')->assertDontSee('Autorizar saída')->assertDontSee('<script>segredo</script>',false);
        $d['tipo_destino']='escola'; $this->post('/copias/cadastros/previa',$d)->assertOk()->assertSee('Pendente — não informado')->assertSee('complementação autorizada');
        Http::assertSent(fn($r)=>$r->method()==='POST' && $r['turmas']===[1] && $r['alunos']===[2] && !isset($r['ator_id']) && !isset($r['destino_espaco_id']) && !isset($r['_ctx']));
        Http::assertNotSent(fn($r)=>str_contains($r->url(),'/lotes') || str_contains($r->url(),'/aprovar'));
        $this->assertNull(session('_old_input'));
    }
    public function test_erros_genericos_sem_selecao_ou_conteudo_no_flash(): void
    {
        [$ctx,$me]=$this->preparar();
        Http::fake(fn($r)=>str_ends_with($r->url(),'/auth/me')?Http::response(['data'=>$me]):Http::response(['message'=>'NOME-PRIVADO-INTERNO'],422));
        $d=['_ctx'=>$ctx,'espaco_id'=>5,'tipo_destino'=>'pessoal','alunos'=>[2]];
        $this->post('/copias/cadastros/previa',$d)->assertStatus(422);
        $this->post('/copias/cadastros/previa',$d+['turmas'=>[1]])->assertStatus(422)->assertDontSee('NOME-PRIVADO-INTERNO');
        $this->assertNull(session('_old_input')); $this->assertStringNotContainsString('NOME-PRIVADO',json_encode(session()->all()));
    }
    public function test_capacidade_flag_e_conexao(): void
    {
        [$ctx,$me]=$this->preparar(['copia.autorizar_destino']); Http::fake(['*'=>Http::response(['data'=>$me])]);
        $this->get('/copias/cadastros?_ctx='.$ctx)->assertForbidden();
        config(['services.copias.interface_ativa'=>false]); $this->get('/copias/cadastros?_ctx='.$ctx)->assertNotFound();
        Http::assertNotSent(fn($r)=>str_contains($r->url(),'/copias'));
    }
    public function test_falha_de_conexao_nao_vira_catalogo_vazio(): void
    {
        [$ctx,$me]=$this->preparar(); Http::fake(function($r) use($me) {
            // O seletor compartilhado consulta opções pela identidade, não pelo token contextual.
            if ($r->method()==='GET' && preg_match('~/auth/(vinculos|acessos-internos)$~',$r->url())) {
                $this->assertTrue($r->hasHeader('Authorization','Bearer '.session('auth_token')));
                return Http::response(['data'=>[]]);
            }

            if(str_ends_with($r->url(),'/auth/me')) return Http::response(['data'=>$me]);
            throw new \Illuminate\Http\Client\ConnectionException('NOME-PRIVADO-INTERNO');
        });
        $this->get('/copias/cadastros?_ctx='.$ctx)->assertStatus(503)->assertDontSee('NOME-PRIVADO-INTERNO');
    }
}
