<?php
namespace Tests\Feature;
use App\Support\ContextoWeb;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class OperacaoCopiaWebTest extends TestCase
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
    private const ID='aaaaaaaa-aaaa-4aaa-8aaa-aaaaaaaaaaaa';
    private function operacao(string $lado='destino'): array
    { return ['lote_id'=>self::ID,'lado'=>$lado,'finalidade'=>'resultados_identificados','estado'=>'aguardando_auditoria',
        'selecionados'=>1,'publicados'=>0,'auditoria_pendente'=>false,'execucao_solicitada'=>false,'pode_solicitar'=>$lado==='destino',
        'pode_revogar'=>true,'acervo_disponivel'=>false]; }
    public function test_acoes_confirmadas_contexto_preservado_e_sem_autor_forjado(): void
    {
        [$ctx,$me]=$this->preparar(['copia.autorizar_destino']);
        Http::fake(fn($r)=>Http::response(['data'=>str_ends_with($r->url(),'/auth/me')?$me:$this->operacao()]));
        $url='/copias/operacao/'.self::ID;
        $this->get($url.'?lado=destino&_ctx='.$ctx)->assertOk()->assertSee('Solicitar processamento')->assertSee('name="_token"',false)
            ->assertSee('name="confirmado"',false)->assertPaginaSemSelecaoPrevia()->assertSee('não apaga cópias já publicadas');
        $this->post($url.'/solicitar',['_ctx'=>$ctx])->assertUnprocessable();
        $this->post($url.'/solicitar',['_ctx'=>$ctx,'confirmado'=>1,'ator_id'=>999,'lado'=>'origem'])->assertRedirect();
        Http::assertSent(fn($r)=>str_ends_with($r->url(),'/solicitar') && $r->data()===['confirmado'=>1] && $r->hasHeader('Authorization','Bearer contexto-resultados'));
        $this->post($url.'/revogar',['_ctx'=>$ctx,'lado'=>'destino','confirmado'=>1])->assertRedirect();
        Http::assertSent(fn($r)=>str_ends_with($r->url(),'/revogar') && $r->data()===['lado'=>'destino','confirmado'=>1]);
        $this->post($url.'/revogar',['_ctx'=>$ctx,'lado'=>'origem','confirmado'=>1])->assertForbidden();
    }
    public function test_bloqueado_orienta_nova_selecao_sem_reabrir(): void
    {
        [$ctx,$me]=$this->preparar(); $d=array_replace($this->operacao('origem'),['estado'=>'bloqueado','execucao_solicitada'=>true,'pode_revogar'=>false]);
        Http::fake(fn($r)=>Http::response(['data'=>str_ends_with($r->url(),'/auth/me')?$me:$d]));
        $this->get('/copias/operacao/'.self::ID.'?lado=origem&_ctx='.$ctx)->assertOk()->assertSee('Preparar nova solicitação')
            ->assertSee('não duplica a cópia')->assertDontSee('name="confirmado"',false)->assertDontSee('Consultar acervo publicado');
        $this->post('/copias/operacao/'.self::ID.'/solicitar',['_ctx'=>$ctx,'confirmado'=>1])->assertForbidden();
    }
    public function test_acervo_exibe_pacote_legado_escapado_sem_formulario(): void
    {
        [$ctx,$me]=$this->preparar(['copia.autorizar_destino']);
        Http::fake(fn($r)=>Http::response(['data'=>str_ends_with($r->url(),'/auth/me')?$me:['lote_id'=>self::ID,'publicado_em'=>'2026-11-02 12:00:00','pacote'=>$this->pacote()]]));
        $this->get('/copias/operacao/'.self::ID.'/acervo?_ctx='.$ctx)->assertOk()->assertSee('Acervo de resultados')->assertSee('Legado sem versão original comprovada')
            ->assertSee('Conferir respostas incluídas')->assertSee('8.00')->assertDontSee('<script>segredo</script>',false)->assertDontSee('name="confirmado"',false);
    }
    public function test_falha_generica_sem_flash_e_flag_fechada(): void
    {
        [$ctx,$me]=$this->preparar(['copia.autorizar_destino']);
        Http::fake(fn($r)=>str_ends_with($r->url(),'/auth/me')?Http::response(['data'=>$me]):Http::response(['message'=>'SEGREDO INTERNO'],409));
        $this->post('/copias/operacao/'.self::ID.'/solicitar',['_ctx'=>$ctx,'confirmado'=>1,'documento'=>'SEGREDO'])->assertStatus(409)->assertDontSee('SEGREDO');
        $this->assertStringNotContainsString('SEGREDO',json_encode(session()->all()));
        config(['services.copias.interface_ativa'=>false]);
        $this->get('/copias/operacao/'.self::ID.'?lado=destino&_ctx='.$ctx)->assertNotFound();
    }
}