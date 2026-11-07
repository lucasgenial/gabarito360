<?php
namespace Tests\Feature;

use App\Models\Usuario;
use App\Support\{CatalogoWidgetsPainel, Permissao};
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class PreferenciasPainelTest extends TestCase
{
    use \Tests\Support\BancoCurriculoDescartavel, \Tests\Support\CenarioDisciplinas;

    private function entrar(int $usuario, int $vinculo): void
    {
        app('auth')->forgetGuards();
        Sanctum::actingAs(Usuario::findOrFail($usuario)->noVinculo($vinculo));
    }
    private function widget(string $id='kpi-turmas', string $section='resumo', int $x=0, int $y=0, int $w=3, int $h=4): array
    {
        return compact('id','section','x','y','w','h');
    }
    private function pedido(int $versao=0, array $extra=[]): array
    {
        return $extra + ['versao'=>$versao, 'chave'=>(string)Str::uuid(), 'acao'=>'salvar', 'layouts'=>['desktop'=>[$this->widget()]]];
    }

    public function test_padrao_nao_grava_dados_e_salvar_persiste_so_geometria_e_apresentacao(): void
    {
        $this->getJson('/api/v1/paineis/preferencias/turmas')->assertOk()->assertHeader('Cache-Control','no-store, private')
            ->assertJsonPath('data.versao',0)->assertJsonPath('data.apresentacao','tabela')
            ->assertJsonCount(0,'data.layouts.desktop')->assertJsonCount(0,'data.layouts.mobile');
        $this->assertDatabaseCount('preferencias_paineis',0);
        $p=$this->pedido(extra:['apresentacao'=>'cards']);
        $this->putJson('/api/v1/paineis/preferencias/turmas',$p)->assertOk()->assertJsonPath('data.versao',1)->assertJsonPath('data.layouts.desktop.0.id','kpi-turmas');
        $this->getJson('/api/v1/paineis/preferencias/turmas')->assertOk()->assertJsonPath('data.apresentacao','cards')->assertJsonPath('data.versao',1);
        $this->assertDatabaseCount('preferencias_painel_eventos',1);
        $this->assertDatabaseCount('turmas',2);$this->assertDatabaseCount('provas',2);
        $this->assertSame(['id','section','x','y','w','h'],array_keys(json_decode(DB::table('preferencias_paineis')->value('desktop'),true)[0]));
    }

    public function test_mobile_desktop_e_restauracao_explicita_sao_independentes_e_idempotentes(): void
    {
        $this->putJson('/api/v1/paineis/preferencias/turmas',$this->pedido(extra:['apresentacao'=>'cards']))->assertOk();
        $m=$this->pedido(1,['layouts'=>['mobile'=>[$this->widget(w:12)]]]);
        $this->putJson('/api/v1/paineis/preferencias/turmas',$m)->assertOk()->assertJsonPath('data.layouts.desktop.0.w',3)->assertJsonPath('data.layouts.mobile.0.w',12)->assertJsonPath('data.versao',2);
        $reset=['acao'=>'restaurar','versao'=>2,'chave'=>(string)Str::uuid(),'alvos'=>['mobile']];
        $this->putJson('/api/v1/paineis/preferencias/turmas',$reset)->assertOk()->assertJsonPath('data.versao',3)->assertJsonCount(0,'data.layouts.mobile')->assertJsonPath('data.layouts.desktop.0.w',3)->assertJsonPath('data.apresentacao','cards');
        $this->putJson('/api/v1/paineis/preferencias/turmas',$reset)->assertOk()->assertJsonPath('data.versao',3)->assertJsonPath('data.repetido',true);
        $this->assertDatabaseCount('preferencias_painel_eventos',3);
        $reset['versao']=3;$reset['chave']=(string)Str::uuid();$reset['alvos']=['desktop','apresentacao'];
        $this->putJson('/api/v1/paineis/preferencias/turmas',$reset)->assertOk()->assertJsonPath('data.apresentacao','tabela')->assertJsonCount(0,'data.layouts.desktop');
    }

    public function test_aba_antiga_chave_reutilizada_e_replay_nao_sobrescrevem_estado_novo(): void
    {
        $p=$this->pedido();
        $this->putJson('/api/v1/paineis/preferencias/turmas',$p)->assertOk();
        $this->putJson('/api/v1/paineis/preferencias/turmas',$this->pedido(0))->assertConflict();
        $this->putJson('/api/v1/paineis/preferencias/turmas',$this->pedido(1,['apresentacao'=>'cards']))->assertOk()->assertJsonPath('data.versao',2);
        $this->putJson('/api/v1/paineis/preferencias/turmas',$p)->assertOk()->assertJsonPath('data.versao',1)->assertJsonPath('data.repetido',true);
        $this->getJson('/api/v1/paineis/preferencias/turmas')->assertOk()->assertJsonPath('data.versao',2)->assertJsonPath('data.apresentacao','cards');
        $p['apresentacao']='cards';
        $this->putJson('/api/v1/paineis/preferencias/turmas',$p)->assertConflict();
        $this->assertDatabaseCount('preferencias_painel_eventos',2);
    }

    public function test_isolamento_entre_usuario_vinculo_espaco_perfil_e_tipo_de_painel(): void
    {
        $this->putJson('/api/v1/paineis/preferencias/turmas',$this->pedido(extra:['apresentacao'=>'cards']))->assertOk();
        $this->getJson('/api/v1/paineis/preferencias/turma')->assertOk()->assertJsonPath('data.versao',0);
        foreach([[1,2],[1,4],[2,3]] as [$u,$v]) {
            $this->entrar($u,$v);
            $this->getJson('/api/v1/paineis/preferencias/turmas')->assertOk()->assertJsonPath('data.versao',0)->assertJsonPath('data.apresentacao','tabela');
        }
        $this->entrar(1,1);
        DB::table('usuario_vinculos')->where('id',1)->update(['perfil'=>'vice_dir_escolar']);
        $this->entrar(1,1);
        $this->getJson('/api/v1/paineis/preferencias/turmas')->assertOk()->assertJsonPath('data.versao',0);
        DB::table('usuario_vinculos')->where('id',1)->update(['perfil'=>'coordenador']);$this->entrar(1,1);
        $this->getJson('/api/v1/paineis/preferencias/turmas')->assertOk()->assertJsonPath('data.apresentacao','cards');
        config(['espacos.pessoal'=>true]);
        DB::table('clientes')->insert(['id'=>3,'tipo'=>'professor','nome'=>'Cliente pessoal fictício','modalidade'=>'interno','situacao'=>'ativo']);
        DB::table('espacos')->insert(['id'=>7,'cliente_id'=>3,'tipo'=>'pessoal','nome'=>'Espaço fictício','situacao'=>'ativo','usuario_proprietario_id'=>2]);
        DB::table('usuario_vinculos')->insert(['id'=>6,'usuario_id'=>2,'espaco_id'=>7,'perfil'=>'professor_autonomo','situacao'=>'ativo','inicio_em'=>now()->subDay()]);
        $this->entrar(2,6);
        $this->getJson('/api/v1/paineis/preferencias/turmas')->assertOk()->assertJsonPath('data.apresentacao','tabela');
        $this->putJson('/api/v1/paineis/preferencias/turmas',$this->pedido())->assertOk();
        $this->entrar(2,3);
        $this->getJson('/api/v1/paineis/preferencias/turmas')->assertOk()->assertJsonPath('data.versao',0);
    }

    public function test_permissao_retirada_filtra_get_e_replay_sem_reaplicar_widget_salvo(): void
    {
        $p=$this->pedido(extra:['layouts'=>['desktop'=>[$this->widget('comparacao-turmas','analises',w:6,h:9)]]]);
        $this->putJson('/api/v1/paineis/preferencias/turmas',$p)->assertOk();
        DB::table('vinculo_permissoes')->insert(['vinculo_id'=>1,'permissao'=>'relatorio.turma','efeito'=>'retirar']);
        $r=$this->getJson('/api/v1/paineis/preferencias/turmas')->assertOk()->assertJsonCount(0,'data.layouts.desktop');
        $this->assertNotContains('comparacao-turmas',array_column($r->json('data.catalogo'),'id'));
        $this->putJson('/api/v1/paineis/preferencias/turmas',$p)->assertUnprocessable();
        DB::table('vinculo_permissoes')->insert(['vinculo_id'=>1,'permissao'=>'turma.ver','efeito'=>'retirar']);
        $this->getJson('/api/v1/paineis/preferencias/turmas')->assertForbidden();
        $this->putJson('/api/v1/paineis/preferencias/turmas',$this->pedido(1))->assertForbidden();
        $this->assertDatabaseCount('preferencias_painel_eventos',1);
    }

    public function test_gravar_mobile_nao_altera_bytes_desktop_temporariamente_oculto_por_permissao(): void
    {
        $p=$this->pedido(extra:['layouts'=>['desktop'=>[$this->widget('comparacao-turmas','analises',w:6,h:9)]]]);
        $this->putJson('/api/v1/paineis/preferencias/turmas',$p)->assertOk();
        $desktop=DB::table('preferencias_paineis')->value('desktop');
        DB::table('vinculo_permissoes')->insert(['vinculo_id'=>1,'permissao'=>'relatorio.turma','efeito'=>'retirar']);
        $this->putJson('/api/v1/paineis/preferencias/turmas',$this->pedido(1,['layouts'=>['mobile'=>[$this->widget(w:12)]]]))->assertOk()->assertJsonCount(0,'data.layouts.desktop');
        $this->assertSame($desktop,DB::table('preferencias_paineis')->value('desktop'));
        DB::table('vinculo_permissoes')->where('vinculo_id',1)->where('permissao','relatorio.turma')->delete();
        $this->getJson('/api/v1/paineis/preferencias/turmas')->assertOk()->assertJsonPath('data.layouts.desktop.0.id','comparacao-turmas');
    }

    public function test_secretaria_com_extras_nao_ve_nem_salva_widget_pedagogico(): void
    {
        foreach(['secretario_escolar','auxiliar_secretaria'] as $perfil) {
            DB::table('usuario_vinculos')->where('id',3)->update(['perfil'=>$perfil]);
            foreach(['relatorio.turma','resultado.ver','prova.ver'] as $cap) DB::table('vinculo_permissoes')->updateOrInsert(['vinculo_id'=>3,'permissao'=>$cap],['efeito'=>'conceder']);
            $this->entrar(2,3);
            $r=$this->getJson('/api/v1/paineis/preferencias/turma')->assertOk();
            $this->assertSame(['turma-cadastro','agenda-administrativa','documentos-administrativos'],array_column($r->json('data.catalogo'),'id'));
            $this->putJson('/api/v1/paineis/preferencias/turma',$this->pedido(extra:['layouts'=>['desktop'=>[$this->widget('distribuicao-acertos','analises',w:6,h:9)]]]))->assertUnprocessable();
        }
        $this->assertDatabaseCount('preferencias_paineis',0);
    }

    public function test_geometrias_invalidas_colisao_html_ids_estranhos_e_autoridade_nao_sao_persistidos(): void
    {
        $base=$this->widget();
        $invalidos=[array_replace($base,['x'=>-1]),array_replace($base,['x'=>12]),array_replace($base,['y'=>199]),array_replace($base,['w'=>1]),array_replace($base,['h'=>9]),array_replace($base,['x'=>'0']),array_replace($base,['section'=>'ensino']),array_replace($base,['id'=>'<script>']),array_replace($base,['nome'=>'Dado proibido']),array_replace($base,['x'=>[]])];
        foreach($invalidos as $w) $this->putJson('/api/v1/paineis/preferencias/turmas',$this->pedido(extra:['layouts'=>['desktop'=>[$w]]]))->assertUnprocessable();
        $this->putJson('/api/v1/paineis/preferencias/turmas',$this->pedido(extra:['layouts'=>['desktop'=>[$base,$base]]]))->assertUnprocessable();
        $this->putJson('/api/v1/paineis/preferencias/turmas',$this->pedido(extra:['layouts'=>['desktop'=>[$base,$this->widget('kpi-em-uso',x:2)]]]))->assertUnprocessable();
        $this->putJson('/api/v1/paineis/preferencias/turmas',$this->pedido(extra:['layouts'=>['mobile'=>[$base]]]))->assertUnprocessable();
        foreach(['usuario_id','vinculo_id','espaco_id','perfil','demos','html','notas'] as $campo) $this->putJson('/api/v1/paineis/preferencias/turmas',$this->pedido(extra:[$campo=>'injetado']))->assertUnprocessable();
        $this->travel(61)->seconds(); // Os primeiros vinte pedidos consumiram a cota real; não desligar middleware.
        foreach([['layouts'=>['tablet'=>[]]],['layouts'=>[]],['apresentacao'=>'html'],['versao'=>'0'],['acao'=>'restaurar','alvos'=>['mobile']],['layouts'=>['desktop'=>array_fill(0,65,$base)]]] as $dados) $this->putJson('/api/v1/paineis/preferencias/turmas',$this->pedido(extra:$dados))->assertUnprocessable();
        $this->getJson('/api/v1/paineis/preferencias/turmas?usuario_id=2')->assertUnprocessable();
        $this->putJson('/api/v1/paineis/preferencias/turmas?versao=0',$this->pedido())->assertUnprocessable();
        $this->assertDatabaseCount('preferencias_paineis',0);$this->assertDatabaseCount('preferencias_painel_eventos',0);
    }

    public function test_espacos_vazios_e_troca_explicita_preservam_exatamente_as_caixas_sem_compactar(): void
    {
        $primeiro=$this->widget(x:6,y:20);$segundo=$this->widget('kpi-em-uso',x:0,y:0,w:4);$terceiro=$this->widget('kpi-arquivadas',x:5,y:30);
        $p=$this->pedido(extra:['layouts'=>['desktop'=>[$primeiro,$segundo,$terceiro]]]);
        $this->putJson('/api/v1/paineis/preferencias/turmas',$p)->assertOk();
        $primeiro['x']=0;$primeiro['y']=0;$segundo['x']=6;$segundo['y']=20;
        $p=$this->pedido(1,['layouts'=>['desktop'=>[$primeiro,$segundo,$terceiro]]]);
        $r=$this->putJson('/api/v1/paineis/preferencias/turmas',$p)->assertOk();$porId=array_column($r->json('data.layouts.desktop'),null,'id');
        $this->assertSame($terceiro,$porId[$terceiro['id']]);$this->assertSame($primeiro,$porId[$primeiro['id']]);$this->assertSame($segundo,$porId[$segundo['id']]);
    }

    public function test_revogacao_vinculo_legado_e_autenticacao_nao_criam_preferencias(): void
    {
        DB::table('usuario_vinculos')->where('id',1)->update(['situacao'=>'suspenso']);
        $this->getJson('/api/v1/paineis/preferencias/turmas')->assertForbidden();
        $this->putJson('/api/v1/paineis/preferencias/turmas',$this->pedido())->assertForbidden();
        $this->entrar(2,3);DB::table('espacos')->where('id',5)->update(['situacao'=>'suspenso']);
        $this->getJson('/api/v1/paineis/preferencias/turmas')->assertForbidden();
        config(['espacos.ativo'=>false,'vinculos.obrigatorio'=>false]);app('auth')->forgetGuards();Sanctum::actingAs(Usuario::findOrFail(1));
        $this->getJson('/api/v1/paineis/preferencias/turmas')->assertForbidden();
        $this->assertDatabaseCount('preferencias_paineis',0);
    }

    public function test_eventos_de_restauracao_nao_podem_ser_alterados_ou_excluidos(): void
    {
        $reset=['acao'=>'restaurar','versao'=>0,'chave'=>(string)Str::uuid(),'alvos'=>['desktop','mobile','apresentacao']];
        $this->putJson('/api/v1/paineis/preferencias/turmas',$reset)->assertOk()->assertJsonPath('data.versao',1);
        foreach(['update','delete'] as $op) {
            try {if($op==='update')DB::table('preferencias_painel_eventos')->update(['acao'=>'salvar']);else DB::table('preferencias_painel_eventos')->delete();$this->fail('Evento foi alterado.');}
            catch(\Illuminate\Database\QueryException $e){$this->assertStringContainsString('imutavel',$e->getMessage());}
        }
        $this->assertDatabaseCount('preferencias_painel_eventos',1);
    }

    public function test_falha_de_recibo_reverte_layout_e_versao_na_mesma_transacao(): void
    {
        $nome='preferencia_recibo_falha';
        if(DB::getDriverName()==='sqlite')DB::unprepared("CREATE TRIGGER $nome BEFORE INSERT ON preferencias_painel_eventos BEGIN SELECT RAISE(ABORT,'Falha ficticia'); END");
        else DB::unprepared("CREATE TRIGGER $nome BEFORE INSERT ON preferencias_painel_eventos FOR EACH ROW SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Falha ficticia'");
        try {$this->putJson('/api/v1/paineis/preferencias/turmas',$this->pedido())->assertServerError();$this->assertDatabaseCount('preferencias_paineis',0);}
        finally {DB::unprepared("DROP TRIGGER $nome");}
    }

    public function test_catalogo_nao_contem_dados_ou_demos_e_respeita_conjuncoes_e_limites(): void
    {
        $r=$this->getJson('/api/v1/paineis/preferencias/turma')->assertOk();
        foreach($r->json('data.catalogo') as $widget) {
            $this->assertSame(['id','section','desktop','mobile'],array_keys($widget));
            $this->assertSame(12,$widget['mobile']['minW']);$this->assertSame(12,$widget['mobile']['maxW']);
        }
        DB::table('vinculo_permissoes')->insert(['vinculo_id'=>1,'permissao'=>'resultado.ver','efeito'=>'retirar']);
        $r=$this->getJson('/api/v1/paineis/preferencias/turma')->assertOk();
        $this->assertNotContains('alunos-recorte',array_column($r->json('data.catalogo'),'id'));
        $this->getJson('/api/v1/paineis/preferencias/outro')->assertNotFound();
    }

    public function test_limite_proprio_nao_compartilha_consultas_e_reabre_depois_da_janela(): void
    {
        for($i=0;$i<20;$i++) $this->getJson('/api/v1/paineis/preferencias/turmas')->assertOk();
        for($i=0;$i<20;$i++) $this->putJson('/api/v1/paineis/preferencias/turmas',$this->pedido($i))->assertOk();
        $this->putJson('/api/v1/paineis/preferencias/turmas',$this->pedido(20))->assertTooManyRequests()->assertHeader('Retry-After');
        $this->getJson('/api/v1/paineis/preferencias/turmas')->assertOk();
        $this->travel(61)->seconds();
        $this->putJson('/api/v1/paineis/preferencias/turmas',$this->pedido(20))->assertOk();
    }

    public function test_sem_autenticacao_nao_consulta_nem_grava_preferencia(): void
    {
        $this->app['auth']->forgetGuards();
        $this->getJson('/api/v1/paineis/preferencias/turmas')->assertUnauthorized();
        $this->putJson('/api/v1/paineis/preferencias/turmas',$this->pedido())->assertUnauthorized();
        $this->assertDatabaseCount('preferencias_paineis',0);
        $u=Usuario::findOrFail(1);
        $this->assertNull($u->vinculoContextoId());
        $pai=$u->createToken('identidade')->accessToken;
        $token=$u->createToken('vinculo-contexto',['vinculo:1','identidade:'.$pai->id])->plainTextToken;
        $this->app['auth']->forgetGuards();
        $this->getJson('/api/v1/paineis/preferencias/turmas',['Authorization'=>'Bearer '.$token])->assertOk();
        $this->app['auth']->forgetGuards();
        $this->putJson('/api/v1/paineis/preferencias/turmas',$this->pedido(),['Authorization'=>'Bearer '.$token])->assertOk();
        $this->assertDatabaseHas('preferencias_paineis',['usuario_id'=>1,'vinculo_id'=>1,'espaco_id'=>5,'perfil'=>'coordenador']);
    }
}
