<?php
namespace Tests\Feature;

use App\Support\{ContextoWeb,NavegacaoWeb,RotasNavegacao};
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/** Fronteira UR4 independente: usa catálogo/pipeline reais, sem alterar o TestCase. */
class NavegacaoRecursosWebTest extends TestCase
{
    private int $executadas = 0;
    private array $contexto;

    protected function setUp(): void
    {
        parent::setUp();
        config(['services.api.base_url'=>'http://api.test/api']);
        $this->withSession(['auth_token'=>'identidade-ur4-ficticia','auth_vinculos'=>true]);
        $this->withCookie(config('session.cookie'),session()->getId())->withCredentials();
        $this->selecionar();
        Http::preventStrayRequests();
        Http::fake(function($r){
            $this->assertSame('/api/v1/auth/me',parse_url($r->url(),PHP_URL_PATH));
            $this->assertTrue($r->hasHeader('Authorization','Bearer token-contexto-ur4'));
            return Http::response(['data'=>$this->contexto]);
        });
    }

    private function selecionar(string $perfil='professor'): string
    {
        // Emissão fora de uma requisição não reutiliza snapshot da requisição anterior do teste.
        foreach(['g360_snapshot','g360_identidade','g360_revisao','vinculo_contexto','vinculo_handle'] as $k)request()->attributes->remove($k);
        $this->contexto=['vinculo_id'=>7,'tipo_contexto'=>'vinculo','token'=>'token-contexto-ur4',
            'perfil'=>$perfil,'permissoes'=>['turma.ver','turma.gerir'],
            'espaco'=>['id'=>13,'tipo'=>'escola','nome'=>'Escola fictícia']];
        return ContextoWeb::ativar($this->contexto);
    }

    private function observar(string $nome): void
    {
        foreach(app('router')->getRoutes() as $rota){
            if(($rota->getAction('ur4')['nome']??null)!==$nome)continue;
            $acao=$rota->getAction();unset($acao['controller']);
            $acao['uses']=function(Request $r){
                $this->executadas++;
                return response()->json(['parametros'=>$r->route()->parameters(),'metodo'=>$r->method(),
                    'corpo'=>$r->request->all(),'consulta'=>$r->query->all(),
                    'erro'=>session('erro'),'antigo'=>session('_old_input')]);
            };
            $rota->setAction($acao);
        }
    }

    private function enviar(array $d,array $extra=[],array $headers=[])
    {
        return $this->postJson($d['url'],['_recurso'=>$d['referencia'],'_revisao'=>ContextoWeb::revisao()]+$extra,
            ['X-G360-Hidratar'=>'1']+$headers);
    }

    public function test_duas_turmas_tem_alvos_imoveis_sem_selecao_global(): void
    {
        $this->observar('turmas.show');
        $a=NavegacaoWeb::destino('turmas.show',['id'=>14]);
        $b=NavegacaoWeb::destino('turmas.show',['id'=>15]);
        $this->assertSame($a['url'],$b['url']);$this->assertNotSame($a['referencia'],$b['referencia']);
        foreach([$a,$b,$a] as $i=>$d)$this->enviar($d)->assertOk()->assertJsonPath('parametros.id',[14,15,14][$i])->assertJsonPath('metodo','GET');
        $this->assertSame(3,$this->executadas);
    }

    public function test_referencia_de_detalhe_nao_pode_abrir_edicao_ou_escrita(): void
    {
        $this->observar('correcao.turma');$this->observar('turmas.update');
        $d=NavegacaoWeb::destino('turmas.show',['id'=>14]);
        foreach(['correcao.turma','turmas.update'] as $nome){
            $outro=NavegacaoWeb::destino($nome,['id'=>14]);$outro['referencia']=$d['referencia'];
            $this->enviar($outro)->assertStatus(409);
        }
        $this->assertSame(0,$this->executadas);Http::assertNothingSent();
    }

    public function test_alvo_no_corpo_conflitante_ou_array_e_constraints_nao_chegam_na_acao(): void
    {
        $this->observar('turmas.show');$d=NavegacaoWeb::destino('turmas.show',['id'=>14]);
        $this->enviar($d,['id'=>15])->assertStatus(422);
        $this->enviar($d,['id'=>[14]])->assertStatus(422);
        foreach(['../14','%2f14','%252f14','14\\15','14?alvo=15','14#15',"14\n15",[14],'0','-14','014'] as $valor){
            $invalido=NavegacaoWeb::destino('turmas.show',['id'=>$valor]);
            $this->enviar($invalido)->assertStatus(422);
        }
        $this->assertSame(0,$this->executadas);Http::assertNothingSent();
    }

    public function test_troca_de_revisao_invalida_referencia_mesmo_com_revisao_nova_no_corpo(): void
    {
        $this->observar('turmas.show');$d=NavegacaoWeb::destino('turmas.show',['id'=>14]);
        $this->selecionar('coordenador');$this->enviar($d)->assertStatus(409);
        $this->assertSame(0,$this->executadas);Http::assertNothingSent();
    }

    public function test_referencia_nao_atravessa_sessao_nem_identidade(): void
    {
        $d=NavegacaoWeb::destino('turmas.show',['id'=>14]);$sid=session()->getId();
        session()->setId(str_repeat('z',40));$this->assertNull(NavegacaoWeb::obter($d['referencia']));
        session()->setId($sid);$this->assertNotNull(NavegacaoWeb::obter($d['referencia']));
        session(['auth_token'=>'outra-identidade']);$this->assertNull(NavegacaoWeb::obter($d['referencia']));
    }

    public function test_post_consulta_exige_csrf_real_antes_de_converter_metodo_para_get(): void
    {
        $this->observar('turmas.show');$d=NavegacaoWeb::destino('turmas.show',['id'=>14]);
        $this->withSession(['_token'=>'csrf-ur4-conhecido']);$this->app->instance('env','local');
        try{
            $this->enviar($d)->assertStatus(419);$this->assertSame(0,$this->executadas);Http::assertNothingSent();
            $this->enviar($d,['_token'=>'csrf-ur4-conhecido'])->assertOk()->assertJsonPath('metodo','GET');
        }finally{$this->app->instance('env','testing');}
    }

    public function test_get_fixo_nao_usa_referencia_em_query_e_legado_nao_abre_recurso(): void
    {
        $this->observar('turmas.show');$d=NavegacaoWeb::destino('turmas.show',['id'=>14]);
        $this->get($d['pagina'].'?_recurso='.$d['referencia'])->assertStatus(409);
        $this->getJson('/app/turmas/14',['X-G360-Revisao'=>ContextoWeb::revisao()])->assertStatus(409);
        $this->assertSame(0,$this->executadas);Http::assertNothingSent();
    }

    public function test_catalogo_nao_tem_parametros_nos_destinos_e_nao_colide_verbo_caminho(): void
    {
        $vistos=[];$total=0;
        foreach(app('router')->getRoutes() as $rota){
            if(!$rota->getAction('ur4'))continue;$total++;
            $this->assertStringNotContainsString('{',$rota->uri());
            foreach($rota->methods() as $metodo){
                $chave=$metodo.' '.$rota->uri();$this->assertArrayNotHasKey($chave,$vistos,'Colisão de destino: '.$chave);
                $vistos[$chave]=$rota->getName();
            }
        }
        $this->assertGreaterThan(200,$total);
    }

    public function test_referencia_expirada_ou_malformada_nao_executa_controller(): void
    {
        $this->observar('turmas.show');$d=NavegacaoWeb::destino('turmas.show',['id'=>14]);
        foreach([['referencia'=>str_repeat('0',48)],['referencia'=>[$d['referencia']]],['referencia'=>'../cache']] as $troca)
            $this->enviar(array_replace($d,$troca))->assertStatus(409);
        $this->travel((int)config('session.lifetime')+1)->minutes();
        $this->enviar($d)->assertStatus(409);$this->travelBack();
        $this->assertSame(0,$this->executadas);Http::assertNothingSent();
    }

    public function test_mutacao_preserva_verbo_logico_e_remove_transporte_do_payload(): void
    {
        $this->observar('turmas.update');$d=NavegacaoWeb::destino('turmas.update',['id'=>14]);
        $r=$this->enviar($d,['nome'=>'Nome editável','_token'=>'csrf-ficticio']);
        $r->assertOk()->assertJsonPath('metodo','PUT')->assertJsonPath('parametros.id',14)
            ->assertJsonPath('corpo',['nome'=>'Nome editável'])->assertJsonPath('consulta',[]);
        $this->assertSame(1,$this->executadas);
    }

    public function test_query_nao_pode_escolher_alvo_nem_operacao(): void
    {
        $this->observar('turmas.show');$d=NavegacaoWeb::destino('turmas.show',['id'=>14]);
        $d['url'].='?id=15&controller=OutraAcao';
        $this->enviar($d)->assertStatus(422);$this->assertSame(0,$this->executadas);Http::assertNothingSent();
    }

    public function test_dois_retornos_do_mesmo_alvo_isolam_old_input_e_mensagens_consumidos_fora_de_ordem(): void
    {
        $this->observar('turmas.show');$base=NavegacaoWeb::destino('turmas.show',['id'=>14]);
        $a=NavegacaoWeb::individualizar($base);$b=NavegacaoWeb::individualizar($base);
        $this->assertNotSame($a['referencia'],$b['referencia']);
        $this->assertSame($a['url'],$b['url']);$this->assertSame($a['pagina'],$b['pagina']);
        foreach([[$a,'Mensagem A','Nome A'],[$b,'Mensagem B','Nome B']] as [$d,$erro,$nome]){
            session()->flash('erro',$erro);session()->flash('_old_input',['nome'=>$nome]);
            NavegacaoWeb::guardarMensagens($d['referencia']);
            $this->assertNull(session('erro'));$this->assertNull(session('_old_input'));
        }
        foreach([[$b,'Mensagem B','Nome B'],[$a,'Mensagem A','Nome A']] as [$d,$erro,$nome]){
            $this->enviar($d)->assertOk()->assertJsonPath('parametros.id',14)
                ->assertJsonPath('erro',$erro)->assertJsonPath('antigo.nome',$nome)
                ->assertHeader('X-G360-Recurso',$d['referencia']);
            $this->assertNull(session('erro'));$this->assertNull(session('_old_input'));
        }
        $this->enviar($b)->assertOk()->assertJsonPath('erro',null)->assertJsonPath('antigo',null);
        $this->assertSame(3,$this->executadas);
    }

    public function test_emissao_nao_acrescenta_permissoes_para_nenhum_dos_quinze_perfis(): void
    {
        $perfis=['secretario_educacao','admin_rede','dir_nucleo','dir_escolar','vice_dir_escolar',
            'coordenador','professor','professor_autonomo','aplicador','aluno','secretario_escolar','auxiliar_secretaria','suporte','desenvolvedor','financeiro'];
        foreach($perfis as $perfil){
            $this->assertArrayHasKey($perfil,\App\Support\Acesso::ROTULOS);
            $this->selecionar($perfil);$antes=$this->contexto;
            $d=NavegacaoWeb::destino('turmas.show',['id'=>14]);
            $this->assertSame('turmas.show',NavegacaoWeb::obter($d['referencia'])['nome']);
            $this->assertSame($antes,$this->contexto);
            foreach(['url','pagina'] as $campo){$this->assertStringNotContainsString('14',$d[$campo]);$this->assertStringNotContainsString($d['referencia'],$d[$campo]);}
        }
        Http::assertNothingSent();
    }
}
