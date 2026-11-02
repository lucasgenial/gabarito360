<?php
namespace Tests\Feature;
use App\Support\ContextoWeb;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class DecisaoResultadosWebTest extends TestCase
{
    private function preparar(array $permissoes=['copia.autorizar_origem']): array
    {
        config(['services.api.base_url'=>'http://api.test/api','services.copias.interface_ativa'=>true]); Http::preventStrayRequests();
        $this->withSession(['auth_token'=>'identidade','auth_vinculos'=>true,'auth_nome'=>'Fictício']);
        $this->withCookie(config('session.cookie'),session()->getId())->withCredentials();
        $me=['token'=>'contexto-resultados','tipo_contexto'=>'vinculo','perfil'=>'dir_escolar','espaco'=>['id'=>5,'tipo'=>'escola','nome'=>'Escola'],'permissoes'=>$permissoes];
        return [ContextoWeb::criar($me),$me];
    }
    private function pacote(): array
    {
        return ['tipo_destino'=>'pessoal','condicao_historica'=>'legado_sem_versao_original',
            'alunos'=>[['nome'=>'Aluno','matricula'=>'MAT-1']],'turmas'=>[['nome'=>'Turma','serie'=>'3','turno'=>'manha','ano_letivo'=>2026]],
            'resultados'=>[['aluno'=>'Aluno','matricula'=>'MAT-1','turma'=>'Turma','prova'=>'Prova <script>segredo</script>',
                'dados'=>['nota_final'=>'8.00','acertos'=>1,'total_questoes'=>1,'status_aprovacao'=>'aprovado','lingua'=>'ING'],
                'respostas'=>[['numero_questao'=>1,'alternativa'=>'B']]]],
            'materiais'=>[['material'=>['prova'=>['titulo'=>'Prova <script>segredo</script>','disciplina'=>'Matemática','serie'=>'3','num_questoes'=>1,'num_alternativas'=>4,
                'nota_maxima'=>'10.00','tipo_pontuacao'=>'igual','anular_se_todas'=>false,'opcao_lingua'=>false],
                'questoes'=>[['numero_questao'=>1,'alternativa'=>'A','alternativa_espanhol'=>null,'peso'=>'1.00','anulada'=>false]]]]]];
    }
    private function decisao(string $lado='origem',bool $evidencia=false): array
    { return ['lote_id'=>'aaaaaaaa-aaaa-4aaa-8aaa-aaaaaaaaaaaa','lado'=>$lado,'origem_nome'=>'Origem','destino_nome'=>'Destino',
        'pacote'=>$this->pacote(),'conteudo_digest'=>str_repeat('c',64),'evidencia_registrada'=>$evidencia,'evidencia_valida'=>$evidencia,
        'evidencia_valida_ate'=>$evidencia?now()->utc()->addHour()->format('Y-m-d H:i:s'):null,'saida_valida_ate'=>null,
        'decisao_registrada'=>false,'decisao_valida'=>false,'valida_ate'=>null]; }
    public function test_codigo_destino_e_referencia_preservam_contexto(): void
    {
        [$ctx,$me]=$this->preparar(['copia.autorizar_destino']); $code=str_repeat('a',64);
        Http::fake(function($r) use($me,$code) {
            $this->assertTrue($r->hasHeader('Authorization','Bearer contexto-resultados'));
            return Http::response(['data'=>str_ends_with($r->url(),'/auth/me')?$me:($r->method()==='POST'?[
                'codigo'=>$code,'valida_ate'=>now()->addMinutes(15)->toISOString(),'destino'=>['nome'=>'Destino']]:['espacos'=>[['id'=>5,'nome'=>'Destino']],'espaco_id'=>5])]);
        });
        $this->get('/copias/receber-resultados?_ctx='.$ctx)->assertOk()->assertSee('Receber resultados')->assertSee('name="_token"',false);
        $this->post('/copias/receber-resultados',['_ctx'=>$ctx,'espaco_id'=>5])->assertOk()->assertSee($code);
        Http::assertSent(fn($r)=>str_ends_with($r->url(),'/resultados/recebimentos') && $r->data()===['espaco_id'=>5]);
        $this->assertStringNotContainsString($code,json_encode(session()->all()));
        $this->post('/copias/resultados/abrir',['_ctx'=>$ctx,'lote_id'=>$this->decisao()['lote_id']])->assertRedirect();
    }
    public function test_pacote_completo_e_tres_confirmacoes_antes_de_criar(): void
    {
        [$ctx,$me]=$this->preparar(); $deps=['provas'=>[2],'alunos'=>[1],'turmas'=>[1]];
        Http::fake(fn($r)=>Http::response(['data'=>str_ends_with($r->url(),'/auth/me')?$me:(str_ends_with($r->url(),'/preparar')?[
            'espaco_id'=>5,'notas'=>[1],'dependencias'=>$deps,'pacote'=>$this->pacote(),'conteudo_digest'=>str_repeat('c',64),
            'recebimento'=>['destino'=>['nome'=>'Destino','tipo'=>'pessoal'],'destino_digest'=>str_repeat('d',64),'valida_ate'=>now()->addMinutes(15)->toISOString()]]:['lote_id'=>$this->decisao()['lote_id']])]));
        $d=['_ctx'=>$ctx,'espaco_id'=>5,'notas'=>[1],'codigo'=>str_repeat('a',64),'tipo_destino'=>'escola','ator_id'=>99];
        $this->post('/copias/resultados/preparar',$d)->assertOk()->assertSee('Conferir respostas incluídas')->assertSee('Conferir gabarito')
            ->assertSee('8.00')->assertSee('10.00')->assertSee('Dependências incluídas')->assertSee('name="confirmado"',false)
            ->assertSee('name="dependencias_confirmadas"',false)->assertSee('name="legado_reconhecido"',false)
            ->assertDontSee('checked',false)->assertDontSee('<script>segredo</script>',false);
        $this->post('/copias/resultados/lotes',$d+['chave'=>$this->decisao()['lote_id'],'dependencias'=>$deps,'destino_digest'=>str_repeat('d',64),'conteudo_digest'=>str_repeat('c',64),
            'confirmado'=>1,'dependencias_confirmadas'=>1,'legado_reconhecido'=>1])->assertRedirect();
        Http::assertSent(fn($r)=>str_ends_with($r->url(),'/lotes') && $r['dependencias']===$deps && !isset($r['ator_id']) && !isset($r['tipo_destino']));
        Http::assertNotSent(fn($r)=>str_contains($r->url(),'/aprovar'));
    }
    public function test_evidencia_guarda_preservacao_e_decisoes_distintas(): void
    {
        [$ctx,$me]=$this->preparar(['copia.autorizar_origem','copia.autorizar_destino']); $d=$this->decisao(); $id=$d['lote_id'];
        Http::fake(function($r) use($me,&$d) { return Http::response(['data'=>str_ends_with($r->url(),'/auth/me')?$me:$d]); });
        $this->get('/copias/resultados/lotes/'.$id.'?lado=origem&_ctx='.$ctx)->assertOk()->assertSee('name="documento"',false)->assertSee('name="guarda"',false)
            ->assertSee('name="preservacao"',false)->assertSee('name="legado_reconhecido"',false)->assertSee('não substitui o prazo de guarda contratual')->assertDontSee('Autorizar saída dos resultados');
        $this->post('/copias/resultados/lotes/'.$id.'/evidencia',['_ctx'=>$ctx,'conteudo_digest'=>$d['conteudo_digest'],
            'documento'=>'DOC-RESTRITO','guarda'=>'GUARDA-RESTRITA','preservacao'=>'PRESERVACAO-RESTRITA','confirmado'=>1,'legado_reconhecido'=>1,
            'valida_ate'=>now()->utc()->addHour()->format('Y-m-d\TH:i:s\Z')])->assertRedirect();
        Http::assertNotSent(fn($r)=>str_contains($r->url(),'/aprovar'));
        foreach(['DOC-RESTRITO','GUARDA-RESTRITA','PRESERVACAO-RESTRITA'] as $t) $this->assertStringNotContainsString($t,json_encode(session()->all()));
        $d=$this->decisao('origem',true);
        $this->get('/copias/resultados/lotes/'.$id.'?lado=origem&_ctx='.$ctx)->assertOk()->assertSee('Autorizar saída dos resultados')->assertDontSee('name="documento"',false);
        $this->post('/copias/resultados/lotes/'.$id.'/aprovar',['_ctx'=>$ctx,'lado'=>'origem','conteudo_digest'=>$d['conteudo_digest'],
            'confirmado'=>1,'legado_reconhecido'=>1,'valida_ate'=>now()->utc()->addMinutes(30)->format('Y-m-d\TH:i:s\Z')])->assertRedirect();
        $d=$this->decisao('destino',true); $d['saida_valida_ate']=now()->utc()->addMinutes(10)->format('Y-m-d H:i:s');
        $this->get('/copias/resultados/lotes/'.$id.'?lado=destino&_ctx='.$ctx)->assertOk()->assertSee('Autorizar entrada dos resultados')->assertSee('name="legado_reconhecido"',false)
            ->assertSee('Conferir respostas incluídas')->assertDontSee('name="guarda"',false)->assertDontSee('Autorizar saída dos resultados');
        Http::assertNotSent(fn($r)=>str_contains($r->url(),'executar'));
    }
    public function test_validacao_e_erro_api_nao_gravam_dados_restritos(): void
    {
        [$ctx,$me]=$this->preparar(); $id=$this->decisao()['lote_id'];
        Http::fake(fn($r)=>str_ends_with($r->url(),'/auth/me')?Http::response(['data'=>$me]):Http::response(['message'=>'DOC-RESTRITO GUARDA-RESTRITA'],409));
        $d=['_ctx'=>$ctx,'conteudo_digest'=>str_repeat('c',64),'documento'=>'DOC-RESTRITO','guarda'=>'GUARDA-RESTRITA','preservacao'=>'PRESERVACAO-RESTRITA',
            'confirmado'=>1,'valida_ate'=>now()->utc()->addHour()->format('Y-m-d\TH:i:s\Z')];
        $this->post('/copias/resultados/lotes/'.$id.'/evidencia',$d)->assertStatus(422);
        $this->post('/copias/resultados/lotes/'.$id.'/evidencia',$d+['legado_reconhecido'=>1])->assertStatus(409)->assertDontSee('DOC-RESTRITO')->assertDontSee('GUARDA-RESTRITA');
        $this->assertNull(session('_old_input')); foreach(['DOC-RESTRITO','GUARDA-RESTRITA','PRESERVACAO-RESTRITA'] as $t) $this->assertStringNotContainsString($t,json_encode(session()->all()));
        $this->get('/copias/receber-resultados?_ctx='.$ctx)->assertForbidden();
        config(['services.copias.interface_ativa'=>false]); $this->get('/copias/resultados/lotes/'.$id.'?lado=origem&_ctx='.$ctx)->assertNotFound();
    }
}
