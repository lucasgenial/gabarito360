<?php
namespace Tests\Feature;
use App\Support\ContextoWeb;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class DecisaoMaterialWebTest extends TestCase
{
    private function preparar(array $permissoes=['copia.autorizar_origem']): array
    {
        config(['services.api.base_url'=>'http://api.test/api','services.copias.interface_ativa'=>true]); Http::preventStrayRequests();
        $this->withSession(['auth_token'=>'identidade','auth_vinculos'=>true,'auth_nome'=>'Fictício']);
        $this->withCookie(config('session.cookie'),session()->getId())->withCredentials();
        $me=['vinculo_id'=>7,'token'=>'contexto-material','tipo_contexto'=>'vinculo','perfil'=>'dir_escolar','espaco'=>['id'=>5,'tipo'=>'escola','nome'=>'Escola fictícia'],'permissoes'=>$permissoes];
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
    private function decisao(string $lado='origem',bool $revisado=false,bool $aprovado=false): array
    { return ['lote_id'=>'aaaaaaaa-aaaa-4aaa-8aaa-aaaaaaaaaaaa','lado'=>$lado,'itens'=>$this->previa()['materiais'],
        'conteudo_digest'=>str_repeat('a',64),'origem_nome'=>'Origem','destino_nome'=>'Destino <script>segredo</script>',
        'revisado'=>$revisado,'aprovado'=>$aprovado,'valida_ate'=>now()->addHour()->toISOString(),'saida_valida_ate'=>null]; }
    public function test_receber_codigo_e_abrir_referencia_no_contexto(): void
    {
        [$ctx,$me]=$this->preparar(['copia.autorizar_destino']); $code=str_repeat('b',64);
        Http::fake(function($r) use($me,$code) {
            // O seletor compartilhado consulta opções pela identidade, não pelo token contextual.
            if ($r->method()==='GET' && preg_match('~/auth/(vinculos|acessos-internos)$~',$r->url())) {
                $this->assertTrue($r->hasHeader('Authorization','Bearer '.session('auth_token')));
                return Http::response(['data'=>[]]);
            }

            $this->assertTrue($r->hasHeader('Authorization','Bearer contexto-material'));
            return Http::response(['data'=>str_ends_with($r->url(),'/auth/me')?$me:($r->method()==='POST'
                ?['codigo'=>$code,'valida_ate'=>now()->addMinutes(15)->toISOString(),'destino'=>['nome'=>'Meu espaço']]:$this->catalogo())]);
        });
        $this->get('/acessos/vinculo-7/copias/receber-material')->assertOk()->assertSee('Gerar código de destino')->assertSee('name="_token"',false);
        $this->post('/acessos/vinculo-7/copias/receber-material',['espaco_id'=>5,'vinculo_id'=>99])->assertOk()->assertSee($code)->assertSee('não autoriza a entrada');
        $this->assertNull(session('_old_input')); $this->assertStringNotContainsString($code,json_encode(session()->all()));
        Http::assertSent(fn($r)=>$r->method()==='POST' && $r->data()===['espaco_id'=>5]);
        $this->post('/acessos/vinculo-7/copias/materiais/abrir',['lote_id'=>$this->decisao()['lote_id']])->assertRedirect(route('copias.material.decisao',['id'=>$this->decisao()['lote_id'],'lado'=>'destino','acesso'=>'vinculo-7']));
    }
    public function test_confirmacao_destino_escape_e_criacao_sem_autorizar(): void
    {
        [$ctx,$me]=$this->preparar(); $code=str_repeat('b',64); $prev=$this->previa()+['conteudo_digest'=>str_repeat('c',64)];
        Http::fake(function($r) use($me,$prev) {
            // O seletor compartilhado consulta opções pela identidade, não pelo token contextual.
            if ($r->method()==='GET' && preg_match('~/auth/(vinculos|acessos-internos)$~',$r->url())) {
                $this->assertTrue($r->hasHeader('Authorization','Bearer '.session('auth_token')));
                return Http::response(['data'=>[]]);
            }

            if(str_ends_with($r->url(),'/auth/me')) return Http::response(['data'=>$me]);
            return Http::response(['data'=>str_ends_with($r->url(),'/preparar')?['previa'=>$prev,'recebimento'=>[
                'destino'=>['nome'=>'Destino <script>segredo</script>','tipo'=>'pessoal'],'valida_ate'=>now()->addMinutes(15)->toISOString(),'destino_digest'=>str_repeat('d',64)]]:['lote_id'=>$this->decisao()['lote_id']]]);
        });
        $this->post('/acessos/vinculo-7/copias/materiais/preparar',['espaco_id'=>5,'provas'=>[2],'codigo'=>$code])->assertOk()
            ->assertSee('Preparar solicitação')->assertSee('name="confirmado"',false)->assertSee('name="_token"',false)->assertDontSee($ctx)->assertDontSee('contexto-cadastro')->assertDontSee('contexto-copia')
            ->assertDontSee('<script>segredo</script>',false)->assertDontSee('Autorizar saída');
        $this->post('/acessos/vinculo-7/copias/materiais/lotes',['espaco_id'=>5,'provas'=>[2],'codigo'=>$code,'confirmado'=>1,
            'chave'=>$this->decisao()['lote_id'],'conteudo_digest'=>str_repeat('c',64),'destino_digest'=>str_repeat('d',64),'ator_id'=>99])->assertRedirect();
        Http::assertSent(fn($r)=>str_ends_with($r->url(),'/lotes') && !isset($r['ator_id']) && !isset($r['_ctx']) && $r['codigo']===$code);
        Http::assertNotSent(fn($r)=>str_contains($r->url(),'aprovar') || str_contains($r->url(),'executar'));
        $this->assertStringNotContainsString($code,json_encode(session()->all()));
    }
    public function test_revisao_e_duas_aprovacoes_sao_acoes_separadas(): void
    {
        [$ctx,$me]=$this->preparar(['copia.autorizar_origem','copia.autorizar_destino']); $d=$this->decisao(); $id=$d['lote_id'];
        Http::fake(function($r) use($me,&$d) {
            // O seletor compartilhado consulta opções pela identidade, não pelo token contextual.
            if ($r->method()==='GET' && preg_match('~/auth/(vinculos|acessos-internos)$~',$r->url())) {
                $this->assertTrue($r->hasHeader('Authorization','Bearer '.session('auth_token')));
                return Http::response(['data'=>[]]);
            }
 return Http::response(['data'=>str_ends_with($r->url(),'/auth/me')?$me:$d]); });
        $this->get('/acessos/vinculo-7/copias/materiais/lotes/'.$id.'?lado=origem')->assertOk()->assertSee('Registrar revisão')->assertDontSee('Autorizar saída')->assertDontSee('<script>segredo</script>',false);
        $this->post('/acessos/vinculo-7/copias/materiais/lotes/'.$id.'/revisar',['conteudo_digest'=>$d['conteudo_digest'],'sem_dados_pessoais'=>1])->assertRedirect();
        Http::assertNotSent(fn($r)=>str_contains($r->url(),'/aprovar'));
        $d['revisado']=true;
        $this->get('/acessos/vinculo-7/copias/materiais/lotes/'.$id.'?lado=origem')->assertOk()->assertSee('Autorizar saída')->assertDontSee('Autorizar entrada');
        $this->post('/acessos/vinculo-7/copias/materiais/lotes/'.$id.'/aprovar',['lado'=>'origem','conteudo_digest'=>$d['conteudo_digest'],
            'valida_ate'=>now()->utc()->addHour()->format('Y-m-d\TH:i:s\Z'),'confirmado'=>1])->assertRedirect();
        $d['aprovado']=true;
        $this->get('/acessos/vinculo-7/copias/materiais/lotes/'.$id.'?lado=origem')->assertOk()->assertSee('Referência para compartilhar')->assertDontSee('Autorizar saída');
        $d=$this->decisao('destino',true); $d['saida_valida_ate']=now()->utc()->addMinutes(10)->format('Y-m-d H:i:s');
        $this->get('/acessos/vinculo-7/copias/materiais/lotes/'.$id.'?lado=destino')->assertOk()->assertSee('Autorizar entrada')->assertDontSee('Autorizar saída')->assertDontSee('Registrar revisão');
        Http::assertNotSent(fn($r)=>str_contains($r->url(),'executar'));
    }
    public function test_validacao_e_falha_api_nao_gravam_codigo_em_flash(): void
    {
        [$ctx,$me]=$this->preparar(); $code=str_repeat('b',64);
        Http::fake(fn($r)=>str_ends_with($r->url(),'/auth/me')?Http::response(['data'=>$me]):Http::response(['message'=>'SEGREDO-INTERNO-'.$code],409));
        $this->post('/acessos/vinculo-7/copias/materiais/preparar',['codigo'=>$code])->assertStatus(422)->assertDontSee($code);
        $this->post('/acessos/vinculo-7/copias/materiais/preparar',['codigo'=>$code,'espaco_id'=>5,'provas'=>[2]])->assertStatus(409)->assertDontSee($code)->assertDontSee('SEGREDO-INTERNO');
        $this->assertNull(session('_old_input')); $this->assertStringNotContainsString($code,json_encode(session()->all()));
    }
    public function test_papel_e_flag_restringem_acesso(): void
    {
        [$ctx,$me]=$this->preparar(); Http::fake(['*'=>Http::response(['data'=>$me])]);
        $this->get('/acessos/vinculo-7/copias/receber-material')->assertForbidden();
        config(['services.copias.interface_ativa'=>false]);
        $this->post('/acessos/vinculo-7/copias/materiais/preparar',[])->assertNotFound();
        Http::assertNotSent(fn($r)=>str_contains($r->url(),'/copias'));
    }
}
