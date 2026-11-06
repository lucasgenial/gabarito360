<?php
namespace Tests\Feature;

use App\Support\ContextoWeb;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class CurriculoProvaWebTest extends TestCase
{
    use \Tests\Support\ContextoSelecionado;
    private int $versao=1;
    private bool $bloqueada=false, $falha=false, $corrida=false;
    private bool $semCurriculo=false, $pessoal=false, $leiturasDivergentes=false;

    private function preparar(array $permissoes=['prova.criar','prova.editar','prova.ver','turma.ver']): string
    {
        Http::swap(new \Illuminate\Http\Client\Factory());
        config(['services.api.base_url'=>'http://api.test/api','disciplinas.ativo'=>true]);Http::preventStrayRequests();
        $this->withSession(['auth_token'=>'identidade-ficticia','auth_vinculos'=>true,'auth_nome'=>'Fictício']);
        $this->withCookie(config('session.cookie'),session()->getId())->withCredentials();
        $me=['token'=>'contexto-ficticio','tipo_contexto'=>'vinculo','vinculo_id'=>1,'perfil'=>$this->pessoal?'professor_autonomo':'coordenador','permissoes'=>$permissoes,
            'espaco'=>['id'=>5,'tipo'=>$this->pessoal?'pessoal':'escola','nome'=>'Espaço fictício']];
        Http::fake(function($r) use($me) {
            $p=parse_url($r->url(),PHP_URL_PATH);
            if($p==='/api/v1/auth/me')return Http::response(['data'=>$me]);
            if($p==='/api/v1/auth/vinculos')return Http::response(['data'=>[]]);
            if($p==='/api/v1/escolas')return Http::response(['data'=>['escolas'=>[['id'=>1,'nome'=>'Escola fictícia']]]]);
            if($p==='/api/v1/turmas')return Http::response(['data'=>['turmas'=>[
                ['id'=>1,'nome'=>'1º A','serie'=>'1','ano_letivo'=>2026,'turno'=>'tarde'],['id'=>3,'nome'=>'1º B','serie'=>'1','ano_letivo'=>2026,'turno'=>'tarde']]]]);
            if($p==='/api/v1/provas/opcoes-disciplinas') {
                if($this->falha)return Http::response(['message'=>'Falha temporária'],503);
                $op=[['id'=>1,'nome'=>'Matemática','sigla'=>'MAT']];if(!in_array(3,$r['turma_ids']??[]))$op[]=['id'=>2,'nome'=>'Física','sigla'=>'FIS'];
                if(!($r['turma_ids']??[]))$op=[];
                $ids=$r['rascunho_ids']??[];
                return Http::response(['data'=>['opcoes'=>$op,'selecionadas'=>array_map(fn($id)=>['id'=>(int)$id,'nome'=>(int)$id===1?'Matemática':'Física','elegivel'=>in_array((int)$id,array_column($op,'id')),'motivo'=>'Confira as atribuições nas turmas.'],$ids),'curriculo_versao'=>isset($r['prova_id'])?($this->semCurriculo?0:$this->versao+($this->leiturasDivergentes?1:0)):0]]);
            }
            if($p==='/api/v1/provas/10' && $r->method()==='PUT') {
                if($this->corrida){$this->versao++;return Http::response(['message'=>'A configuração mudou.'],409);}
                return Http::response(['data'=>$this->prova()]);
            }
            if($p==='/api/v1/provas/10')return Http::response(['data'=>$this->prova()]);
            if($p==='/api/v1/provas' && $r->method()==='POST')return Http::response(['data'=>['id'=>10]],201);
            return Http::response(['data'=>[]]);
        });
        $this->selecionarContextoFixture($me);
        return ContextoWeb::referencia($me);
    }

    private function prova(): array
    {
        return ['id'=>10,'titulo'=>'Prova existente','disciplina'=>'Matemática','serie'=>'1','num_questoes'=>1,'num_alternativas'=>4,
            'nota_maxima'=>10,'tipo_pontuacao'=>'igual','data_aplicacao'=>null,'gerar_cartao_pdf'=>true,'anular_se_todas'=>false,
            'status'=>'rascunho','pode_editar'=>true,'pode_copiar'=>false,'criado_por_nome'=>'Coordenação','turmas'=>[['id'=>1,'nome'=>'1º A','serie'=>'1']],
            'curriculo'=>$this->semCurriculo?null:['versao'=>$this->versao,'disciplinas'=>[['id'=>1,'nome'=>'Matemática']]],
            'organizacao_curricular'=>['editavel'=>!$this->bloqueada,'configurado'=>!$this->semCurriculo]];
    }

    private function nenhumaGravacao(): void
    {
        Http::assertNotSent(fn($r)=>in_array($r->method(),['POST','PUT','DELETE']) && !str_ends_with(parse_url($r->url(),PHP_URL_PATH),'/opcoes-disciplinas'));
    }

    public function test_nova_prova_tem_ordem_correta_e_preparacao_sem_gravacao_preserva_dados(): void
    {
        $ctx=$this->preparar();$this->get('/app/provas/nova')->assertOk()->assertSeeInOrder(['Turmas</h2>','Disciplinas da prova</h2>','Dados da prova</h2>'],false)->assertDontSee('editor-grid')->assertSee('Criar prova e preencher gabarito');
        $this->post('/app/provas/preparar',['acao'=>'adicionar','turma_ids'=>[1],'nova_disciplina_id'=>1,'titulo'=>'Rascunho <script>seguro</script>','serie'=>'1','num_questoes'=>30,'gerar_cartao_pdf'=>1])
            ->assertOk()->assertSee('value="Rascunho &lt;script&gt;seguro&lt;/script&gt;"',false)->assertSee('name="disciplina_ids[]" value="1"',false)->assertSee('value="30"',false)->assertDontSee('<script>seguro</script>',false);
        $this->nenhumaGravacao();
    }

    public function test_incompatibilidade_falha_e_remocao_mantem_escolhas_e_acoes_sem_js(): void
    {
        $ctx=$this->preparar();$url='/app/provas/preparar';
        $d=['acao'=>'opcoes','turma_ids'=>[1,3],'disciplina_ids'=>[2],'titulo'=>'Preenchido'];
        $this->post($url,$d)->assertOk()->assertSee('Precisam de revisão')->assertSee('Confira as atribuições')->assertSee('name="disciplina_ids[]" value="2"',false);
        $this->falha=true;$this->post($url,$d)->assertOk()->assertSee('Não foi possível conferir as opções')->assertSee('data-falha="1"',false)->assertSee('name="disciplina_ids[]" value="2"',false)->assertSee('value="Preenchido"',false);
        $this->falha=false;$this->post($url,array_replace($d,['acao'=>'remover:2']))->assertOk()->assertDontSee('name="disciplina_ids[]" value="2"',false)->assertSee('Nenhuma disciplina adicionada');
        $this->nenhumaGravacao();
    }

    public function test_fragmento_leitura_nao_substitui_dados_e_criacao_final_envia_ids_e_booleanos(): void
    {
        $ctx=$this->preparar();
        $this->withHeader('X-Curriculo-Fragmento','1')->post('/app/provas/preparar',['acao'=>'adicionar','turma_ids'=>[1],'nova_disciplina_id'=>1])->assertOk()->assertSee('data-curriculo-editor',false)->assertDontSee('name="titulo"',false);
        $this->flushHeaders(); $this->withHeader('X-G360-Revisao', session('auth_contexto_revisao'));
        $this->post('/app/provas',['escola_id'=>1,'titulo'=>'Criar','serie'=>'1','formato_avaliacao'=>'gabarito','num_questoes'=>1,'num_alternativas'=>4,'turma_ids'=>[1],'disciplina_ids'=>[1]])->assertRedirectContains('/app/provas/10/gabarito/editar');
        Http::assertSent(fn($r)=>$r->method()==='POST' && str_ends_with($r->url(),'/v1/provas') && $r['disciplina_ids']===[1] && !isset($r['disciplina']) && $r['opcao_lingua']===false);
    }

    public function test_conflito_preserva_proposta_exige_revisao_e_nao_reenvia_automaticamente(): void
    {
        $ctx=$this->preparar();$d=['turma_ids'=>[3],'disciplina_ids'=>[1],'curriculo_versao'=>0];
        $url='/app/provas/10/curriculo';
        $this->post($url,$d)->assertConflict()->assertSee('A configuração foi alterada')->assertSee('name="curriculo_versao" value="0"',false)->assertSee('value="3" checked',false);
        $this->nenhumaGravacao();
        $this->post('/app/provas/10/curriculo/preparar',$d+['acao'=>'rever'])->assertOk()->assertSee('Confira as alterações')->assertSee('Turmas atuais')->assertSee('name="curriculo_versao" value="1"',false)->assertSee('name="revisao_pendente" value="1"',false);
        $d['curriculo_versao']=1;$d['revisao_pendente']=1;
        $this->from($url)->post($url,$d)->assertUnprocessable()->assertSee('Confira o preenchimento');$this->nenhumaGravacao();
        $this->post($url,$d+['conferido'=>1])->assertRedirectContains('/app/provas/10');
        $this->corrida=true;
        $this->post($url,$d+['conferido'=>1])->assertConflict()->assertSee('Atualizar estado e revisar')->assertSee('name="curriculo_versao" value="1"',false);
    }

    public function test_prova_aplicada_impede_configuracao_e_metadados_nao_enviam_curriculo(): void
    {
        $ctx=$this->preparar();$this->bloqueada=true;
        $this->get('/app/provas/10/curriculo')->assertRedirectContains('/app/provas/10');
        $this->get('/app/provas/10')->assertOk()->assertSee('Classificação preservada')->assertDontSee('name="disciplina"',false)->assertDontSee('name="turma_ids[]"',false);
        $this->put('/app/provas/10',['titulo'=>'Título novo'])->assertRedirect();
        Http::assertSent(fn($r)=>$r->method()==='PUT' && $r->data()===['titulo'=>'Título novo']);
    }

    public function test_rota_nova_exige_contexto_capacidade_e_flag(): void
    {
        $ctx=$this->preparar(['prova.ver']);$this->get('/app/provas/nova')->assertForbidden();
        $this->post('/app/provas/preparar',['acao'=>'opcoes'])->assertForbidden();
        $this->get('/app/provas/10/curriculo')->assertForbidden();
        config(['disciplinas.ativo'=>false]);$this->post('/app/provas/preparar')->assertNotFound();
        $this->nenhumaGravacao();
    }

    public function test_revisao_nao_aceita_versao_de_leituras_divergentes(): void
    {
        $ctx=$this->preparar();$this->leiturasDivergentes=true;
        $this->post('/app/provas/10/curriculo/preparar',['acao'=>'rever','curriculo_versao'=>1,'turma_ids'=>[1],'disciplina_ids'=>[1]])
            ->assertOk()->assertSee('A configuração foi alterada')->assertDontSee('Confira as alterações')->assertSee('name="curriculo_versao" value="1"',false);
        $this->nenhumaGravacao();
    }

    public function test_copia_sem_curriculo_exige_configuracao_e_pessoal_preserva_tela_anterior(): void
    {
        $ctx=$this->preparar();$this->semCurriculo=true;
        $this->get('/app/provas/10')->assertOk()->assertSee('Configure turmas e disciplinas antes de publicar.');
        $this->get('/app/provas/10/curriculo')->assertOk()->assertSee('name="curriculo_versao" value="0"',false)->assertDontSee('name="disciplina_ids[]"',false);
        $this->pessoal=true;$ctx=$this->preparar();
        $this->get('/app/provas/nova')->assertOk()->assertSee('editor-grid')->assertSee('name="disciplina"',false)->assertDontSee('data-prova-curriculo',false);
        $this->nenhumaGravacao();
    }

    public function test_validacao_final_reexibe_erro_inline_sem_redirecionar_para_post_de_preparacao(): void
    {
        $ctx=$this->preparar();
        $this->from('/app/provas/preparar')->post('/app/provas',['escola_id'=>1,'titulo'=>'Preservado','serie'=>'1','formato_avaliacao'=>'gabarito','num_questoes'=>0,'num_alternativas'=>4,'turma_ids'=>[1],'disciplina_ids'=>[1]])
            ->assertUnprocessable()->assertSee('value="Preservado"',false)->assertSee('id="erro-num_questoes"',false)->assertSee('aria-invalid="true"',false)->assertDontSee('validation.min');
        $this->nenhumaGravacao();
    }
}
