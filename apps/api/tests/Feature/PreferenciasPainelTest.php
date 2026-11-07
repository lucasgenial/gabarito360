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
    private function widget(string $id='cadastros-turno', string $section='analises', int $x=0, int $y=0, int $w=6, int $h=11): array
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
        $this->putJson('/api/v1/paineis/preferencias/turmas',$p)->assertOk()->assertJsonPath('data.versao',1)->assertJsonPath('data.layouts.desktop.0.id','cadastros-turno');
        $this->getJson('/api/v1/paineis/preferencias/turmas')->assertOk()->assertJsonPath('data.apresentacao','cards')->assertJsonPath('data.versao',1);
        $this->assertDatabaseCount('preferencias_painel_eventos',1);
        $this->assertDatabaseCount('turmas',2);$this->assertDatabaseCount('provas',2);
        $this->assertSame(['id','section','x','y','w','h'],array_keys(json_decode(DB::table('preferencias_paineis')->value('desktop'),true)[0]));
    }

    public function test_mobile_desktop_e_restauracao_explicita_sao_independentes_e_idempotentes(): void
    {
        $this->putJson('/api/v1/paineis/preferencias/turmas',$this->pedido(extra:['apresentacao'=>'cards']))->assertOk();
        $m=$this->pedido(1,['layouts'=>['mobile'=>[$this->widget(w:12)]]]);
        $this->putJson('/api/v1/paineis/preferencias/turmas',$m)->assertOk()->assertJsonPath('data.layouts.desktop.0.w',6)->assertJsonPath('data.layouts.mobile.0.w',12)->assertJsonPath('data.versao',2);
        $reset=['acao'=>'restaurar','versao'=>2,'chave'=>(string)Str::uuid(),'alvos'=>['mobile']];
        $this->putJson('/api/v1/paineis/preferencias/turmas',$reset)->assertOk()->assertJsonPath('data.versao',3)->assertJsonCount(0,'data.layouts.mobile')->assertJsonPath('data.layouts.desktop.0.w',6)->assertJsonPath('data.apresentacao','cards');
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
        $this->getJson('/api/v1/paineis/preferencias/turma',['X-G360-Painel-Turma'=>'1'])->assertOk()->assertJsonPath('data.versao',0);
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
        $p=$this->pedido(extra:['layouts'=>['desktop'=>[$this->widget('comparacao-turmas','analises',w:6,h:12)]]]);
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
        $p=$this->pedido(extra:['layouts'=>['desktop'=>[$this->widget('comparacao-turmas','analises',w:6,h:12)]]]);
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
            $r=$this->getJson('/api/v1/paineis/preferencias/turma',['X-G360-Painel-Turma'=>'1'])->assertOk();
            $this->assertSame(['agenda-administrativa','documentos-administrativos'],array_column($r->json('data.catalogo'),'id'));
            $this->putJson('/api/v1/paineis/preferencias/turma',$this->pedido(extra:['layouts'=>['desktop'=>[$this->widget('distribuicao-acertos','analises',w:6,h:12)]]]),['X-G360-Painel-Turma'=>'1'])->assertUnprocessable();
        }
        $this->assertDatabaseCount('preferencias_paineis',0);
    }

    public function test_geometrias_invalidas_colisao_html_ids_estranhos_e_autoridade_nao_sao_persistidos(): void
    {
        $base=$this->widget();
        $invalidos=[array_replace($base,['x'=>-1]),array_replace($base,['x'=>12]),array_replace($base,['y'=>199]),array_replace($base,['w'=>1]),array_replace($base,['h'=>7]),array_replace($base,['x'=>'0']),array_replace($base,['section'=>'ensino']),array_replace($base,['id'=>'<script>']),array_replace($base,['nome'=>'Dado proibido']),array_replace($base,['x'=>[]])];
        foreach($invalidos as $w) $this->putJson('/api/v1/paineis/preferencias/turmas',$this->pedido(extra:['layouts'=>['desktop'=>[$w]]]))->assertUnprocessable();
        $this->putJson('/api/v1/paineis/preferencias/turmas',$this->pedido(extra:['layouts'=>['desktop'=>[$base,$base]]]))->assertUnprocessable();
        $this->putJson('/api/v1/paineis/preferencias/turmas',$this->pedido(extra:['layouts'=>['desktop'=>[$base,$this->widget('comparacao-turmas',x:2,h:12)]]]))->assertUnprocessable();
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
        $primeiro=$this->widget(x:6,y:20,w:4,h:12);$segundo=$this->widget('comparacao-turmas',x:0,y:0,w:4,h:12);$terceiro=$this->widget('cobertura-disciplinas',x:5,y:40,w:4,h:12);
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
        $r=$this->getJson('/api/v1/paineis/preferencias/turma',['X-G360-Painel-Turma'=>'1'])->assertOk();
        $this->assertSame(['alunos-destaques','alunos-atencao'],array_slice(array_column($r->json('data.catalogo'),'id'),0,2));
        $this->assertSame(['alunos-destaques','alunos-atencao'],array_slice(array_column($r->json('data.padrao.desktop'),'id'),0,2));
        foreach(array_slice($r->json('data.catalogo'),0,2) as $alunos){
            $this->assertSame('estudantes',$alunos['section']);
            $this->assertSame([10,12,20,6],[$alunos['desktop']['minH'],$alunos['desktop']['defaultH'],$alunos['desktop']['maxH'],$alunos['desktop']['defaultW']]);
        }
        $this->putJson('/api/v1/paineis/preferencias/turma',$this->pedido(extra:['layouts'=>['desktop'=>[$this->widget('alunos-recorte',h:12)]]]),['X-G360-Painel-Turma'=>'1'])->assertUnprocessable();
        foreach($r->json('data.catalogo') as $widget) {
            $this->assertSame(['id','section','desktop','mobile'],array_keys($widget));
            $this->assertSame(12,$widget['mobile']['minW']);$this->assertSame(12,$widget['mobile']['maxW']);
            if(in_array($widget['id'],['distribuicao-acertos','resultados-avaliacao','situacoes-registradas','periodos-escolares','habilidades','reavaliacoes'],true))foreach(['desktop','mobile'] as $modo){
                $this->assertSame([8,9,20],[$widget[$modo]['minH'],$widget[$modo]['defaultH'],$widget[$modo]['maxH']]);
            }
            if(in_array($widget['id'],['agenda-pedagogica','agenda-administrativa','proximos-compromissos'],true))$this->assertSame([12,14],[$widget['desktop']['minH'],$widget['desktop']['defaultH']]);
        }
        DB::table('vinculo_permissoes')->insert(['vinculo_id'=>1,'permissao'=>'resultado.ver','efeito'=>'retirar']);
        $r=$this->getJson('/api/v1/paineis/preferencias/turma',['X-G360-Painel-Turma'=>'1'])->assertOk();
        foreach(['alunos-destaques','alunos-atencao','alunos-recorte'] as $id)$this->assertNotContains($id,array_column($r->json('data.catalogo'),'id'));
        DB::table('vinculo_permissoes')->where('vinculo_id',1)->where('permissao','resultado.ver')->delete();
        DB::table('vinculo_permissoes')->insert(['vinculo_id'=>1,'permissao'=>'aluno.ver','efeito'=>'retirar']);
        $r=$this->getJson('/api/v1/paineis/preferencias/turma',['X-G360-Painel-Turma'=>'1'])->assertOk();
        foreach(['alunos-destaques','alunos-atencao'] as $id)$this->assertNotContains($id,array_column($r->json('data.catalogo'),'id'));
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

    public function test_altura_desejada_individual_persiste_sem_perder_h_efetivo_e_replay_e_estrito(): void
    {
        $a=$this->widget(h:16)+['altura_desejada'=>11];
        $b=$this->widget('comparacao-turmas',x:6,h:16)+['altura_desejada'=>16];
        $p=$this->pedido(extra:['layouts'=>['desktop'=>[$a,$b]]]);
        $this->putJson('/api/v1/paineis/preferencias/turmas',$p)->assertOk()->assertJsonPath('data.layouts.desktop.0.h',16)
            ->assertJsonPath('data.layouts.desktop.0.altura_desejada',11)->assertJsonPath('data.layouts.desktop.1.altura_desejada',16);
        $this->getJson('/api/v1/paineis/preferencias/turmas')->assertOk()->assertJsonPath('data.layouts.desktop.0.altura_desejada',11);
        $this->assertSame([$a,$b],json_decode(DB::table('preferencias_paineis')->value('desktop'),true));
        $this->putJson('/api/v1/paineis/preferencias/turmas',$p)->assertOk()->assertJsonPath('data.repetido',true)->assertJsonPath('data.versao',1);
        $this->assertDatabaseCount('preferencias_painel_eventos',1);
        $p['layouts']['desktop'][0]['altura_desejada']=12;
        $this->putJson('/api/v1/paineis/preferencias/turmas',$p)->assertConflict();
        $legado=$this->pedido(1);
        $this->putJson('/api/v1/paineis/preferencias/turmas',$legado)->assertOk()->assertJsonMissingPath('data.layouts.desktop.0.altura_desejada');
        $this->putJson('/api/v1/paineis/preferencias/turmas',$legado)->assertOk()->assertJsonPath('data.repetido',true);
        $this->assertSame(['id','section','x','y','w','h'],array_keys(json_decode(DB::table('preferencias_paineis')->value('desktop'),true)[0]));
    }

    public function test_altura_desejada_recusa_tipos_invalidos_fora_limites_e_campos_nao_previstos(): void
    {
        foreach([7,17,11.5,'11',null,[],false]as$altura){
            $w=$this->widget(h:16)+['altura_desejada'=>$altura];
            $this->putJson('/api/v1/paineis/preferencias/turmas',$this->pedido(extra:['layouts'=>['desktop'=>[$w]]]))->assertUnprocessable();
        }
        $w=$this->widget(h:11)+['altura_desejada'=>12];
        $this->putJson('/api/v1/paineis/preferencias/turmas',$this->pedido(extra:['layouts'=>['desktop'=>[$w]]]))->assertUnprocessable();
        $w=$this->widget()+['altura_minima'=>4];
        $this->putJson('/api/v1/paineis/preferencias/turmas',$this->pedido(extra:['layouts'=>['desktop'=>[$w]]]))->assertUnprocessable();
        $this->assertDatabaseCount('preferencias_paineis',0);$this->assertDatabaseCount('preferencias_painel_eventos',0);
    }

    public function test_altura_desejada_nao_fura_catalogo_ou_filtragem_por_permissao_nem_altera_mobile(): void
    {
        $w=$this->widget('comparacao-turmas','analises',w:6,h:16)+['altura_desejada'=>12];
        $p=$this->pedido(extra:['layouts'=>['desktop'=>[$w]]]);
        $this->putJson('/api/v1/paineis/preferencias/turmas',$p)->assertOk()->assertJsonPath('data.layouts.desktop.0.altura_desejada',12)->assertJsonCount(0,'data.layouts.mobile');
        DB::table('vinculo_permissoes')->insert(['vinculo_id'=>1,'permissao'=>'relatorio.turma','efeito'=>'retirar']);
        $this->getJson('/api/v1/paineis/preferencias/turmas')->assertOk()->assertJsonCount(0,'data.layouts.desktop');
        $this->putJson('/api/v1/paineis/preferencias/turmas',$p)->assertUnprocessable();
        $this->assertDatabaseCount('preferencias_painel_eventos',1);
    }

    public function test_turmas_individuais_tem_alvos_escopados_e_preferencias_independentes(): void
    {
        DB::table('turmas')->insert(['id'=>3,'escola_id'=>1,'espaco_id'=>5,'nome'=>'Outra turma fictícia','serie'=>'1','turno'=>'tarde','ano_letivo'=>2026,'ativo'=>false]);
        $p=$this->pedido(extra:['layouts'=>['desktop'=>[$this->widget('distribuicao-acertos',h:11)]]]);
        $h1=['X-G360-Painel-Turma'=>'1'];$h3=['X-G360-Painel-Turma'=>'3'];
        $this->putJson('/api/v1/paineis/preferencias/turma',$p,$h1)->assertOk()->assertJsonPath('data.origem_disposicao','usuario');
        $this->getJson('/api/v1/paineis/preferencias/turma',$h3)->assertOk()->assertJsonPath('data.versao',0)->assertJsonPath('data.origem_disposicao','perfil');
        // A chave é idempotente no alvo, não um seletor global capaz de trocar a outra aba.
        $this->putJson('/api/v1/paineis/preferencias/turma',$p,$h3)->assertOk()->assertJsonPath('data.versao',1);
        $p=$this->pedido(1,['apresentacao'=>'cards','layouts'=>['desktop'=>[$this->widget('distribuicao-acertos',h:11)]]]);
        $this->putJson('/api/v1/paineis/preferencias/turma',$p,$h1)->assertOk()->assertJsonPath('data.versao',2);
        $this->getJson('/api/v1/paineis/preferencias/turma',$h3)->assertOk()->assertJsonPath('data.versao',1)->assertJsonPath('data.apresentacao','tabela');
        $rows=DB::table('preferencias_paineis')->orderBy('recurso_id')->get();
        $this->assertSame([1,3],$rows->pluck('recurso_id')->map(fn($id)=>(int)$id)->all());
        $this->assertCount(2,$rows->pluck('contexto_hash')->unique());$this->assertSame(['turma'],$rows->pluck('recurso_tipo')->unique()->all());
        $this->getJson('/api/v1/paineis/preferencias/turma',['X-G360-Painel-Turma'=>'2'])->assertNotFound();
        $this->entrar(2,3);DB::enableQueryLog();DB::flushQueryLog();
        $this->getJson('/api/v1/paineis/preferencias/turma',$h3)->assertNotFound();
        $this->assertSame([],array_values(array_filter(DB::getQueryLog(),fn($q)=>str_contains($q['query'],'preferencias_paineis'))));
    }

    public function test_header_de_turma_e_estrito_e_nao_aceita_autoridade_no_corpo(): void
    {
        $this->getJson('/api/v1/paineis/preferencias/turma')->assertUnprocessable();
        foreach(['','0','01','-1','1.0','1e0','1,3','9223372036854775808']as$valor)
            $this->getJson('/api/v1/paineis/preferencias/turma',['X-G360-Painel-Turma'=>$valor])->assertUnprocessable();
        $this->getJson('/api/v1/paineis/preferencias/turmas',['X-G360-Painel-Turma'=>'1'])->assertUnprocessable();
        foreach(['recurso_tipo','recurso_id','padrao','origem_disposicao']as$campo)
            $this->putJson('/api/v1/paineis/preferencias/turmas',$this->pedido(extra:[$campo=>'forjado']))->assertUnprocessable();
        $this->getJson('/api/v1/paineis/preferencias/turmas?perfil=dir_escolar')->assertUnprocessable();
        $this->assertDatabaseCount('preferencias_paineis',0);
    }

    public function test_padrao_por_perfil_e_somente_leitura_e_usuario_tem_precedencia_ate_restaurar(): void
    {
        $outro=config('paineis.padroes.professor.turmas');
        config(['paineis.padroes.coordenador.turmas.larguras.cadastros-turno'=>12]);
        $this->assertSame($outro,config('paineis.padroes.professor.turmas'));
        $r=$this->getJson('/api/v1/paineis/preferencias/turmas')->assertOk()->assertJsonPath('data.origem_disposicao','perfil')->assertJsonPath('data.padrao.desktop.0.w',12);
        $padrao=$r->json('data.padrao.desktop');
        $this->assertSame($padrao,app(\App\Services\PreferenciasPainel::class)->validar($this->pedido(extra:['layouts'=>['desktop'=>$padrao]]),$r->json('data.catalogo'))['layouts']['desktop']);
        $linhas=collect($padrao)->groupBy(fn($w)=>$w['section'].':'.$w['y']);
        foreach($linhas as $linha){$this->assertSame(12,$linha->sum('w'));$this->assertCount(1,$linha->pluck('h')->unique());$this->assertSame($linha->max('altura_desejada'),$linha[0]['h']);}
        $this->putJson('/api/v1/paineis/preferencias/turmas',$this->pedido(extra:['layouts'=>['desktop'=>[$this->widget(w:4)]]]))->assertOk()->assertJsonPath('data.origem_disposicao','usuario');
        config(['paineis.padroes.coordenador.turmas.larguras.cadastros-turno'=>6]);
        $this->getJson('/api/v1/paineis/preferencias/turmas')->assertOk()->assertJsonPath('data.layouts.desktop.0.w',4)->assertJsonPath('data.padrao.desktop.0.w',6)->assertJsonPath('data.origem_disposicao','usuario');
        $this->putJson('/api/v1/paineis/preferencias/turmas',['versao'=>1,'chave'=>(string)Str::uuid(),'acao'=>'restaurar','alvos'=>['desktop']])->assertOk()->assertJsonPath('data.origem_disposicao','perfil')->assertJsonPath('data.padrao.desktop.0.w',6);
        $this->assertDatabaseCount('preferencias_painel_eventos',2);
    }

    public function test_resumo_cadastral_e_kpis_sao_fixos_fora_do_catalogo_e_do_payload(): void
    {
        foreach(['turmas','turma']as$painel){
            $headers=$painel==='turma'?['X-G360-Painel-Turma'=>'1']:[];
            $r=$this->getJson('/api/v1/paineis/preferencias/'.$painel,$headers)->assertOk();
            foreach(array_merge($r->json('data.catalogo'),$r->json('data.padrao.desktop'))as$w)$this->assertNotSame('resumo',$w['section']);
            $id=$painel==='turmas'?'kpi-turmas':'kpi-media';
            $this->putJson('/api/v1/paineis/preferencias/'.$painel,$this->pedido(extra:['layouts'=>['desktop'=>[$this->widget($id,'resumo',w:3,h:4)]]]),$headers)->assertUnprocessable();
        }
        DB::table('usuario_vinculos')->where('id',3)->update(['perfil'=>'secretario_escolar']);$this->entrar(2,3);
        $this->putJson('/api/v1/paineis/preferencias/turma',$this->pedido(extra:['layouts'=>['desktop'=>[$this->widget('turma-cadastro','resumo',w:3,h:5)]]]),['X-G360-Painel-Turma'=>'1'])->assertUnprocessable();
        $this->assertDatabaseCount('preferencias_paineis',0);
    }
}
