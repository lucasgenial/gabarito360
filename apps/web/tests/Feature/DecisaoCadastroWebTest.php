<?php
namespace Tests\Feature;
use App\Support\ContextoWeb;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class DecisaoCadastroWebTest extends TestCase
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
    private function decisao(string $lado='origem',bool $evidencia=false): array
    { return $this->previa()+['lote_id'=>'aaaaaaaa-aaaa-4aaa-8aaa-aaaaaaaaaaaa','lado'=>$lado,'origem_nome'=>'Origem','destino_nome'=>'Destino <script>segredo</script>',
        'conteudo_digest'=>str_repeat('c',64),'tem_pendencias'=>false,'evidencia_registrada'=>$evidencia,'evidencia_valida'=>$evidencia,
        'evidencia_valida_ate'=>$evidencia?now()->utc()->addHour()->format('Y-m-d H:i:s'):null,'saida_valida_ate'=>null,
        'decisao_registrada'=>false,'decisao_valida'=>false,'valida_ate'=>null,
        'conciliacao'=>$lado==='destino'?['sem_bloqueios'=>true,'digest'=>str_repeat('d',64),'itens'=>[['entidade'=>'turma','codigo'=>'novo'],['entidade'=>'aluno','codigo'=>'novo']]]:null]; }
    public function test_destino_emite_codigo_exclusivo_e_abre_referencia(): void
    {
        [$ctx,$me]=$this->preparar(['copia.autorizar_destino']); $code=str_repeat('a',64);
        Http::fake(function($r) use($me,$code) {
            $this->assertTrue($r->hasHeader('Authorization','Bearer contexto-cadastro'));
            return Http::response(['data'=>str_ends_with($r->url(),'/auth/me')?$me:($r->method()==='POST'?[
                'codigo'=>$code,'valida_ate'=>now()->addMinutes(15)->toISOString(),'destino'=>['nome'=>'Escola']]:$this->catalogo())]);
        });
        $this->get('/copias/receber-cadastro?_ctx='.$ctx)->assertOk()->assertSee('Receber cadastro')->assertSee('name="_token"',false);
        $this->post('/copias/receber-cadastro',['_ctx'=>$ctx,'espaco_id'=>5])->assertOk()->assertSee($code);
        Http::assertSent(fn($r)=>str_ends_with($r->url(),'/cadastros/recebimentos') && $r->data()===['espaco_id'=>5]);
        $this->assertStringNotContainsString($code,json_encode(session()->all()));
        $this->post('/copias/cadastros/abrir',['_ctx'=>$ctx,'lote_id'=>$this->decisao()['lote_id']])->assertRedirect(route('copias.cadastro.decisao',['id'=>$this->decisao()['lote_id'],'lado'=>'destino','_ctx'=>$ctx]));
    }
    public function test_confirmar_tipo_real_e_criar_sem_autorizar(): void
    {
        [$ctx,$me]=$this->preparar(); $prev=$this->previa('escola')+['conteudo_digest'=>str_repeat('c',64)];
        Http::fake(fn($r)=>Http::response(['data'=>str_ends_with($r->url(),'/auth/me')?$me:(str_ends_with($r->url(),'/preparar')?[
            'previa'=>$prev,'recebimento'=>['destino'=>['nome'=>'Destino <script>segredo</script>','tipo'=>'escola'],'destino_digest'=>str_repeat('d',64),'valida_ate'=>now()->addMinutes(15)->toISOString()]]:['lote_id'=>$this->decisao()['lote_id']])]));
        $d=['_ctx'=>$ctx,'espaco_id'=>5,'turmas'=>[1],'alunos'=>[2],'codigo'=>str_repeat('a',64),'tipo_destino'=>'pessoal','ator_id'=>99];
        $this->post('/copias/cadastros/preparar',$d)->assertOk()->assertSee('Tipo real do destino: escolar')->assertSee('Pendente — não informado')
            ->assertSee('name="confirmado"',false)->assertDontSee('<script>segredo</script>',false);
        $this->post('/copias/cadastros/lotes',$d+['chave'=>$this->decisao()['lote_id'],'destino_digest'=>str_repeat('d',64),'conteudo_digest'=>str_repeat('c',64),'confirmado'=>1])->assertRedirect();
        Http::assertSent(fn($r)=>str_ends_with($r->url(),'/lotes') && !isset($r['tipo_destino']) && !isset($r['ator_id']) && !isset($r['_ctx']) && $r['turmas']===[1]);
        Http::assertNotSent(fn($r)=>str_contains($r->url(),'/aprovar') || str_contains($r->url(),'/evidencia'));
    }
    public function test_evidencia_e_saida_sao_distintas_documento_nao_vai_ao_flash(): void
    {
        [$ctx,$me]=$this->preparar(); $d=$this->decisao(); $id=$d['lote_id'];
        Http::fake(function($r) use($me,&$d) { return Http::response(['data'=>str_ends_with($r->url(),'/auth/me')?$me:$d]); });
        $this->get('/copias/cadastros/lotes/'.$id.'?lado=origem&_ctx='.$ctx)->assertOk()->assertSee('Registrar evidência')->assertSee('name="_token"',false)
            ->assertSee($ctx)->assertDontSee('Autorizar saída do cadastro')->assertDontSee('<script>segredo</script>',false);
        $this->post('/copias/cadastros/lotes/'.$id.'/evidencia',['_ctx'=>$ctx,'conteudo_digest'=>$d['conteudo_digest'],'documento'=>'DOCUMENTO-RESTRITO',
            'confirmado'=>1,'valida_ate'=>now()->utc()->addHour()->format('Y-m-d\TH:i:s\Z')])->assertRedirect();
        Http::assertNotSent(fn($r)=>str_contains($r->url(),'/aprovar'));
        $this->assertStringNotContainsString('DOCUMENTO-RESTRITO',json_encode(session()->all()));
        $d=$this->decisao('origem',true);
        $this->get('/copias/cadastros/lotes/'.$id.'?lado=origem&_ctx='.$ctx)->assertOk()->assertSee('Autorizar saída do cadastro')->assertDontSee('name="documento"',false);
        $this->post('/copias/cadastros/lotes/'.$id.'/aprovar',['_ctx'=>$ctx,'lado'=>'origem','conteudo_digest'=>$d['conteudo_digest'],'confirmado'=>1,
            'valida_ate'=>now()->utc()->addMinutes(30)->format('Y-m-d\TH:i:s\Z')])->assertRedirect();
        $d['decisao_registrada']=true; $d['decisao_valida']=true; $d['valida_ate']=now()->addHour()->toISOString();
        $this->get('/copias/cadastros/lotes/'.$id.'?lado=origem&_ctx='.$ctx)->assertOk()->assertSee('Referência para compartilhar')->assertDontSee('Autorizar saída do cadastro');
    }
    public function test_destino_concilia_e_conflito_impede_botao(): void
    {
        [$ctx,$me]=$this->preparar(['copia.autorizar_destino']); $d=$this->decisao('destino',true); $id=$d['lote_id'];
        $d['saida_valida_ate']=now()->utc()->addMinutes(10)->format('Y-m-d H:i:s');
        Http::fake(function($r) use($me,&$d) { return Http::response(['data'=>str_ends_with($r->url(),'/auth/me')?$me:$d]); });
        $this->get('/copias/cadastros/lotes/'.$id.'?lado=destino&_ctx='.$ctx)->assertOk()->assertSee('Conferência no destino')->assertSee('Autorizar entrada do cadastro')
            ->assertSee('name="conciliacao_digest"',false)->assertDontSee('name="documento"',false)->assertDontSee('Autorizar saída do cadastro');
        $this->post('/copias/cadastros/lotes/'.$id.'/aprovar',['_ctx'=>$ctx,'lado'=>'destino','conteudo_digest'=>$d['conteudo_digest'],
            'conciliacao_digest'=>$d['conciliacao']['digest'],'confirmado'=>1,'valida_ate'=>now()->utc()->addMinutes(5)->format('Y-m-d\TH:i:s\Z')])->assertRedirect();
        Http::assertSent(fn($r)=>str_ends_with($r->url(),'/aprovar') && $r['lado']==='destino' && $r['conciliacao_digest']===str_repeat('d',64));
        $d['conciliacao']['sem_bloqueios']=false; $d['conciliacao']['itens'][1]['codigo']='conflito_matricula';
        $this->get('/copias/cadastros/lotes/'.$id.'?lado=destino&_ctx='.$ctx)->assertOk()->assertSee('matrícula já existente')->assertDontSee('Autorizar entrada do cadastro');
    }
    public function test_falhas_nao_expoem_documento_codigo_nem_dados_internos(): void
    {
        [$ctx,$me]=$this->preparar(); $id=$this->decisao()['lote_id'];
        Http::fake(fn($r)=>str_ends_with($r->url(),'/auth/me')?Http::response(['data'=>$me]):Http::response(['message'=>'DOCUMENTO-RESTRITO'],409));
        $e=['_ctx'=>$ctx,'documento'=>'DOCUMENTO-RESTRITO','conteudo_digest'=>str_repeat('c',64),'valida_ate'=>now()->utc()->addHour()->format('Y-m-d\TH:i:s\Z')];
        $this->post('/copias/cadastros/lotes/'.$id.'/evidencia',$e)->assertStatus(422)->assertDontSee('DOCUMENTO-RESTRITO');
        $this->post('/copias/cadastros/lotes/'.$id.'/evidencia',$e+['confirmado'=>1])->assertStatus(409)->assertDontSee('DOCUMENTO-RESTRITO');
        $this->post('/copias/cadastros/preparar',['_ctx'=>$ctx,'codigo'=>str_repeat('a',64)])->assertStatus(422);
        $this->assertNull(session('_old_input')); $this->assertStringNotContainsString('DOCUMENTO-RESTRITO',json_encode(session()->all()));
        $this->assertStringNotContainsString(str_repeat('a',64),json_encode(session()->all()));
        $this->get('/copias/receber-cadastro?_ctx='.$ctx)->assertForbidden();
        config(['services.copias.interface_ativa'=>false]); $this->get('/copias/cadastros/lotes/'.$id.'?lado=origem&_ctx='.$ctx)->assertNotFound();
    }
}
