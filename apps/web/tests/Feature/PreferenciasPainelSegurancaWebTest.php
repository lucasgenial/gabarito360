<?php
namespace Tests\Feature;

use App\Support\{ContextoWeb, NavegacaoWeb};
use Illuminate\Support\Facades\Http;
use Tests\Support\{ContextoSelecionado, NavegacaoHttp};
use Tests\TestCase;

/** Consumidor real BFF/UR4; não substitui a autorização ou os testes da API. */
class PreferenciasPainelSegurancaWebTest extends TestCase
{
    public function test_resposta_de_organizacao_incompleta_ou_contraditoria_e_502_sem_put(): void
    {
        $d=$this->dto();$d['catalogo'][0]['id']='distribuicao-acertos';$d['catalogo'][0]['section']='analises';
        $d['layouts']['desktop'][0]['id']='distribuicao-acertos';$d['layouts']['desktop'][0]['section']='analises';
        $d['layouts']['desktop'][0]+=['ordem_card'=>1,'ordem_secao'=>0];
        $this->preparar(data:$d);
        $this->consultarJson('/app/paineis/preferencias/turma/14')->assertOk()->assertJsonPath('data.layouts.desktop.0.ordem_card',1);
        $incompleta=$d;unset($incompleta['layouts']['desktop'][0]['ordem_secao']);
        $divergente=$d;$divergente['catalogo'][]=array_replace($d['catalogo'][0],['id'=>'situacoes-registradas']);
        $divergente['layouts']['desktop'][]=array_replace($d['layouts']['desktop'][0],['id'=>'situacoes-registradas','x'=>3,'ordem_card'=>0]);
        foreach([$incompleta,$divergente] as $malformada){
            $this->preparar(data:$malformada);
            $this->consultarJson('/app/paineis/preferencias/turma/14')->assertStatus(502)->assertJsonMissingPath('data.layouts');
            Http::assertNotSent(fn($r)=>$r->method()==='PUT');
        }
    }
    use ContextoSelecionado, NavegacaoHttp;
    private array $opcoesPrefs=[];

    private function dto(): array
    {
        return ['versao'=>2,'apresentacao'=>'tabela','layouts'=>['desktop'=>[['id'=>'kpi-turmas','section'=>'resumo','x'=>0,'y'=>0,'w'=>3,'h'=>4]],'mobile'=>[]],
            'catalogo'=>[['id'=>'kpi-turmas','section'=>'resumo','desktop'=>['minW'=>2,'maxW'=>12,'minH'=>4,'maxH'=>8,'defaultW'=>3,'defaultH'=>4],
                'mobile'=>['minW'=>12,'maxW'=>12,'minH'=>4,'maxH'=>8,'defaultW'=>12,'defaultH'=>4]]], 'repetido'=>false];
    }

    private function preparar(int $status=200, ?array $data=null, array $headers=[], array $extras=[], array $caps=['turma.ver'], string $perfil='professor'): void
    {
        config(['services.api.base_url'=>'http://api.test/api']);
        Http::swap(new \Illuminate\Http\Client\Factory);Http::preventStrayRequests();$this->opcoesPrefs=[];
        $this->withSession(['auth_token'=>'identidade-prefs-ficticia','auth_vinculos'=>true,'auth_nome'=>'Pessoa fictícia']);
        $me=['vinculo_id'=>7,'tipo_contexto'=>'vinculo','token'=>'contexto-prefs-ficticio','perfil'=>$perfil,'permissoes'=>$caps,
            'espaco'=>['id'=>5,'tipo'=>'escola','nome'=>'Escola fictícia']];
        $this->selecionarContextoFixture($me);
        $dto=$data??$this->dto();
        Http::fake(function($r,$options)use($me,$status,$dto,$headers,$extras){
            $path=parse_url($r->url(),PHP_URL_PATH);
            if($path==='/api/v1/auth/me')return Http::response(['data'=>$me]);
            if($path==='/api/v1/auth/logout')return Http::response(['data'=>true]);
            if(str_starts_with($path,'/api/v1/paineis/preferencias/')){$this->opcoesPrefs[]=$options;return Http::response(['data'=>$dto]+$extras,$status,$headers);}
            return Http::response(['message'=>'Caminho fictício não previsto.'],404);
        });
    }

    public function test_consulta_e_put_usam_destino_fixo_csrf_revisao_e_bearer_contextual_sem_autoridade_do_corpo(): void
    {
        $this->preparar();
        $this->consultarJson('/app/paineis/preferencias/turmas')->assertOk()->assertHeader('Cache-Control','no-store, private')->assertJsonPath('data.versao',2);
        $payload=['versao'=>2,'chave'=>'4ea91eba-b8cf-4bed-84a3-077c050ac18b','acao'=>'salvar','apresentacao'=>'cards','layouts'=>['mobile'=>[]]];
        $this->alterarRecursoJson('PUT','/app/paineis/preferencias/turmas',$payload+['usuario_id'=>999,'vinculo_id'=>888,'espaco_id'=>777,'perfil'=>'admin_rede','token'=>'forjado','consulta'=>'ignorar'])->assertOk();
        Http::assertSent(fn($r)=>$r->method()==='PUT' && parse_url($r->url(),PHP_URL_PATH)==='/api/v1/paineis/preferencias/turmas'
            && $r->data()===$payload && $r->hasHeader('Authorization','Bearer contexto-prefs-ficticio'));
        $destino=NavegacaoWeb::destino('paineis.preferencias.salvar',['painel'=>'turmas']);
        $this->assertStringNotContainsString('turmas',parse_url($destino['url'],PHP_URL_PATH));
        $this->assertEmpty(parse_url($destino['url'],PHP_URL_QUERY));
    }

    public function test_401_intercalado_apos_me_encerra_toda_sessao_e_revoga_identidade(): void
    {
        $this->preparar(401);
        $this->consultarJson('/app/paineis/preferencias/turmas')->assertUnauthorized()->assertJsonPath('login_necessario',true)
            ->assertSessionMissing(['auth_token','auth_contexto_ativo','auth_contexto_dados','auth_contexto_revisao']);
        Http::assertSent(fn($r)=>parse_url($r->url(),PHP_URL_PATH)==='/api/v1/auth/logout' && $r->hasHeader('Authorization','Bearer identidade-prefs-ficticia'));
    }

    public function test_401_nativo_redireciona_somente_para_login_sem_repetir_put(): void
    {
        $this->preparar(401);
        $this->alterarRecurso('PUT','/app/paineis/preferencias/turmas',['versao'=>2,'chave'=>'4ea91eba-b8cf-4bed-84a3-077c050ac18b','acao'=>'restaurar','alvos'=>['mobile']])
            ->assertRedirect(route('login'))->assertSessionMissing(['auth_token','auth_contexto_ativo']);
        $this->assertCount(1,$this->opcoesPrefs);
    }

    public function test_429_preserva_retry_after_e_nao_vaza_diagnostico_upstream(): void
    {
        $this->preparar(429,headers:['Retry-After'=>'37'],extras:['message'=>'DIAGNOSTICO_PRIVADO','errors'=>['cpf'=>['12345678901']]]);
        $this->consultarJson('/app/paineis/preferencias/turmas')->assertTooManyRequests()->assertHeader('Retry-After','37')
            ->assertDontSee('DIAGNOSTICO_PRIVADO')->assertDontSee('12345678901')->assertSessionHas('auth_token','identidade-prefs-ficticia');
    }

    public function test_redirecionamento_api_nao_e_seguido_ou_republicado_pelo_bff(): void
    {
        $this->preparar(302,headers:['Location'=>'https://destino-ficticio.invalid/coleta?token=SEGREDO_REDIRECT']);
        $this->consultarJson('/app/paineis/preferencias/turmas')->assertStatus(502)->assertHeaderMissing('Location')->assertDontSee('SEGREDO_REDIRECT');
        $this->alterarRecursoJson('PUT','/app/paineis/preferencias/turmas',['versao'=>2,'chave'=>'4ea91eba-b8cf-4bed-84a3-077c050ac18b','acao'=>'restaurar','alvos'=>['mobile']])->assertStatus(502);
        $this->assertCount(2,$this->opcoesPrefs);
        // A fake não segue redirects: a opção do transporte também precisa estar fechada.
        foreach($this->opcoesPrefs as $options)$this->assertSame(false,$options['allow_redirects']??null);
        Http::assertNotSent(fn($r)=>str_contains($r->url(),'destino-ficticio.invalid'));
    }

    public function test_formas_2xx_invalidas_sao_recusadas_502_sem_conteudo_estranho(): void
    {
        $d=$this->dto();$casos=[];
        foreach(['versao','layouts','catalogo','apresentacao']as$k){$c=$d;$c[$k]='DADO_PRIVADO_INVALIDO';$casos[]=$c;}
        $c=$d;$c['layouts']['desktop'][0]['x']='0';$casos[]=$c;
        $c=$d;$c['layouts']['desktop'][0]['section']='ensino';$casos[]=$c;
        $c=$d;$c['layouts']['desktop'][]=$c['layouts']['desktop'][0];$casos[]=$c;
        $c=$d;$c['catalogo'][0]['desktop']['maxW']=13;$casos[]=$c;
        foreach($casos as $dto){$this->preparar(data:$dto);$this->consultarJson('/app/paineis/preferencias/turmas')->assertStatus(502)->assertDontSee('DADO_PRIVADO_INVALIDO')->assertJsonMissingPath('data');}
    }

    public function test_projecao_2xx_nao_expoe_campos_desconhecidos_em_nenhum_nivel(): void
    {
        $d=$this->dto();$d['usuario']=['cpf'=>'SEGREDO_USUARIO'];$d['layouts']['diagnostico']='SEGREDO_LAYOUTS';
        $d['layouts']['desktop'][0]['nome']='SEGREDO_WIDGET';$d['catalogo'][0]['dados']='SEGREDO_CATALOGO';$d['catalogo'][0]['desktop']['credencial']='SEGREDO_LIMITE';
        $this->preparar(data:$d);
        $r=$this->consultarJson('/app/paineis/preferencias/turmas');
        $this->assertContains($r->status(),[200,502]);
        foreach(['SEGREDO_USUARIO','SEGREDO_LAYOUTS','SEGREDO_WIDGET','SEGREDO_CATALOGO','SEGREDO_LIMITE']as$segredo)$r->assertDontSee($segredo);
    }

    public function test_erros_4xx_nao_repassam_corpo_arbitrario_ou_contexto_da_api(): void
    {
        $this->preparar(422,extras:['message'=>'SEGREDO_EXCECAO','errors'=>['cpf'=>['SEGREDO_PESSOAL'],'debug'=>['SEGREDO_INFRA']]]);
        $this->consultarJson('/app/paineis/preferencias/turmas')->assertUnprocessable()->assertDontSee('SEGREDO_EXCECAO')
            ->assertDontSee('SEGREDO_PESSOAL')->assertDontSee('SEGREDO_INFRA');
    }

    public function test_csrf_ausente_revisao_antiga_e_acesso_retirado_impedem_put_antes_da_api(): void
    {
        $this->preparar();
        $destino=NavegacaoWeb::destino('paineis.preferencias.salvar',['painel'=>'turmas']);
        $this->withSession(['_token'=>'csrf-prefs-ficticio']);$this->app->instance('env','local');
        try {
            $this->postJson($destino['url'],['_recurso'=>$destino['referencia'],'_revisao'=>ContextoWeb::revisao()],['X-G360-Hidratar'=>'1'])->assertStatus(419);
        } finally {$this->app->instance('env','testing');}
        Http::assertNothingSent();
        $this->postJson($destino['url'],['_recurso'=>$destino['referencia'],'_revisao'=>'REVISAO_ANTIGA','_token'=>'csrf-prefs-ficticio'],['X-G360-Hidratar'=>'1'])->assertConflict();
        Http::assertNothingSent();
        $this->preparar(caps:[]);
        $this->alterarRecursoJson('PUT','/app/paineis/preferencias/turmas',['versao'=>2,'chave'=>'4ea91eba-b8cf-4bed-84a3-077c050ac18b','acao'=>'restaurar','alvos'=>['desktop']])->assertForbidden();
        Http::assertNotSent(fn($r)=>$r->method()==='PUT');
    }

    public function test_administrativos_consultam_e_salvam_configuracao_tecnica_sem_grant_pedagogico(): void
    {
        foreach(['secretario_escolar','auxiliar_secretaria'] as $perfil)foreach(['turmas','turma'] as $painel){
            $d=$this->dto();$id='agenda-administrativa';$uri='/app/paineis/preferencias/'.$painel.($painel==='turma'?'/14':'');
            $d['catalogo'][0]['id']=$id;$d['layouts']['desktop'][0]['id']=$id;
            $d['catalogo'][0]['section']='administrativo';$d['layouts']['desktop'][0]['section']='administrativo';
            $d['notas']=['PEDAGOGIA_NAO_PUBLICAVEL'];
            $caps=['turma.ver','aluno.ver','relatorio.turma','resultado.ver','prova.ver','*'];
            $this->preparar(data:$d,caps:$caps,perfil:$perfil);
            $this->consultarJson($uri)->assertOk()->assertJsonPath('data.catalogo.0.id',$id)
                ->assertDontSee('PEDAGOGIA_NAO_PUBLICAVEL')->assertDontSee('comparacao-turmas')->assertDontSee('distribuicao-acertos');
            $payload=['versao'=>2,'chave'=>'4ea91eba-b8cf-4bed-84a3-077c050ac18b','acao'=>'salvar','layouts'=>['desktop'=>$d['layouts']['desktop']]];
            $this->alterarRecursoJson('PUT',$uri,$payload)->assertOk()->assertJsonPath('data.catalogo.0.id',$id)->assertDontSee('PEDAGOGIA_NAO_PUBLICAVEL');
            Http::assertSent(fn($r)=>$r->method()==='PUT'&&$r->data()===$payload);
            // A nova exceção técnica não abre a rota pedagógica mesmo com extras declarados.
            $this->alterarRecursoJson('POST','/app/turmas/14/painel/pdf')->assertForbidden();
            Http::assertNotSent(fn($r)=>str_contains($r->url(),'/painel/pdf'));
            $this->preparar(caps:['*','relatorio.turma'],perfil:$perfil);
            $this->consultarJson($uri)->assertForbidden();
            $this->alterarRecursoJson('PUT',$uri,$payload)->assertForbidden();
            Http::assertNotSent(fn($r)=>str_contains($r->url(),'/paineis/preferencias/'));
        }
    }

    public function test_duas_referencias_de_turmas_mantem_alvos_independentes_sem_ids_na_url(): void
    {
        $this->preparar();
        $a=NavegacaoWeb::destino('paineis.preferencias.consultar',['painel'=>'turma','turmaId'=>14]);
        $b=NavegacaoWeb::destino('paineis.preferencias.consultar',['painel'=>'turma','turmaId'=>15]);
        foreach([$a,$b] as $d){
            $this->assertStringNotContainsString('/14',$d['url']);$this->assertStringNotContainsString('/15',$d['url']);
            $this->assertEmpty(parse_url($d['url'],PHP_URL_QUERY));
        }
        foreach([[$b,'15'],[$a,'14']] as [$d,$id]){
            $this->postJson($d['url'],['_recurso'=>$d['referencia'],'_revisao'=>ContextoWeb::revisao(),'_token'=>csrf_token(),'turma_id'=>999],
                ['X-G360-Hidratar'=>'1','X-G360-Painel-Turma'=>'999'])->assertOk();
            Http::assertSent(fn($r)=>str_contains($r->url(),'/paineis/preferencias/turma')&&$r->hasHeader('X-G360-Painel-Turma',$id)&&!str_contains($r->url(),'?'));
        }
        $this->postJson($a['url'],['_recurso'=>$a['referencia'],'_revisao'=>ContextoWeb::revisao(),'_token'=>csrf_token(),'turmaId'=>15],
            ['X-G360-Hidratar'=>'1'])->assertStatus(422);
    }
}
