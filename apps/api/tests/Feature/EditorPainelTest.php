<?php
namespace Tests\Feature;

use App\Models\Usuario;
use App\Support\ComposicaoPainel;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class EditorPainelTest extends TestCase
{
    use \Tests\Support\BancoCurriculoDescartavel, \Tests\Support\CenarioDisciplinas;
    private const URL='/api/v1/paineis/preferencias/turmas';
    private const HEADERS=['X-G360-Painel-Editor'=>'3'];
    private function atual(): array { return $this->getJson(self::URL,self::HEADERS)->assertOk()->json('data'); }
    private function pedido(array $d,array $s): array { return ['versao'=>$d['versao'],'chave'=>(string)Str::uuid(),'acao'=>'salvar','catalogo_chave'=>$d['editor']['catalogo_chave'],'composicao'=>$s]; }

    public function test_editor_opt_in_nao_altera_resposta_antiga_nem_grava_ao_ler():void
    {
        $this->getJson(self::URL)->assertOk()->assertJsonMissingPath('data.editor');
        $d=$this->atual();$this->assertSame(3,$d['editor']['schema']);$this->assertCount(3,array_filter($d['editor']['padrao']['sections'],fn($n)=>$n['parent']===null));
        $this->assertArrayHasKey('turmas-lista',$d['editor']['composicao']['widgets']);
        $this->assertDatabaseCount('preferencias_paineis',0);$this->assertDatabaseCount('preferencias_painel_eventos',0);
        $this->getJson(self::URL,['X-G360-Painel-Editor'=>'4'])->assertUnprocessable();
    }

    public function test_composicao_titulos_ocultacao_e_avulso_persistem_sem_alterar_dados_escolares():void
    {
        $d=$this->atual();$s=$d['editor']['composicao'];$s['sections'][0]['title']='Meu painel';$s['widgets']['devolutivas']['active']=false;
        $s['widgets']['cadastros-turno']['parent']=null;$s['widgets']['cadastros-turno']['x']=0;$s['widgets']['cadastros-turno']['y']=350;
        $p=$this->pedido($d,$s);$this->putJson(self::URL,$p,self::HEADERS)->assertOk()->assertJsonPath('data.versao',1)->assertJsonPath('data.editor.composicao.sections.0.title','Meu painel')->assertJsonPath('data.editor.composicao.widgets.devolutivas.active',false);
        $this->assertSame($s,$this->atual()['editor']['composicao']);$this->assertDatabaseCount('turmas',2);$this->assertDatabaseCount('provas',2);
        $this->putJson(self::URL,$p,self::HEADERS)->assertOk()->assertJsonPath('data.repetido',true);$this->assertDatabaseCount('preferencias_painel_eventos',1);
    }

    public function test_cliente_antigo_preserva_coluna_nova_byte_a_byte_e_historico():void
    {
        $d=$this->atual();$s=$d['editor']['composicao'];$s['sections'][0]['title']='Título pessoal';
        $this->putJson(self::URL,$this->pedido($d,$s),self::HEADERS)->assertOk();
        $raw=DB::table('preferencias_paineis')->value('composicao');$audit=DB::table('preferencias_painel_eventos')->first();
        $this->putJson(self::URL,['versao'=>1,'chave'=>(string)Str::uuid(),'acao'=>'salvar','apresentacao'=>'cards'])->assertOk()->assertJsonMissingPath('data.editor');
        $this->assertSame($raw,DB::table('preferencias_paineis')->value('composicao'));$this->assertEquals($audit,DB::table('preferencias_painel_eventos')->where('id',$audit->id)->first());
        $this->assertSame('Título pessoal',$this->atual()['editor']['composicao']['sections'][0]['title']);
    }

    public function test_catalogo_obsoleto_e_versao_concorrente_nao_sobrescrevem():void
    {
        $d=$this->atual();$p=$this->pedido($d,$d['editor']['composicao']);$bad=$p;$bad['catalogo_chave']=str_repeat('0',64);
        $this->putJson(self::URL,$bad,self::HEADERS)->assertConflict();$this->assertDatabaseCount('preferencias_paineis',0);
        $this->putJson(self::URL,$p,self::HEADERS)->assertOk();$p['chave']=(string)Str::uuid();$this->putJson(self::URL,$p,self::HEADERS)->assertConflict();$this->assertDatabaseCount('preferencias_painel_eventos',1);
    }

    public function test_arvore_rejeita_ciclos_ids_extras_campos_de_contexto_e_sobreposicao():void
    {
        $d=$this->atual();$s=$d['editor']['composicao'];
        $casos=[];$b=$s;$b['sections'][0]['parent']=$b['sections'][0]['id'];$casos[]=$b;
        $b=$s;$b['sections'][0]['parent']=$b['sections'][1]['id'];$b['sections'][1]['parent']='nao-existe';$casos[]=$b;
        $b=$s;$b['sections'][0]['parent']=$b['sections'][1]['id'];$b['sections'][1]['parent']=[];$casos[]=$b;
        $b=$s;$b['sections'][0]['headerRows']=null;$casos[]=$b;
        $b=$s;$b['usuario_id']=2;$casos[]=$b;
        $b=$s;$b['widgets']['nao-autorizado']=$b['widgets']['cadastros-turno'];$casos[]=$b;
        $b=$s;$b['sections'][1]['x']=$b['sections'][0]['x'];$b['sections'][1]['y']=$b['sections'][0]['y'];$casos[]=$b;
        $b=$s;$b['widgets']['cadastros-turno']['h']=999;$casos[]=$b;
        foreach($casos as $bad)$this->putJson(self::URL,$this->pedido($d,$bad),self::HEADERS)->assertUnprocessable();
        $this->assertDatabaseCount('preferencias_paineis',0);
        $this->putJson(self::URL,$this->pedido($d,$s))->assertUnprocessable();
    }

    public function test_preferencia_de_um_vinculo_nao_aparece_em_outro():void
    {
        $d=$this->atual();$s=$d['editor']['composicao'];$s['sections'][0]['title']='Privado do vínculo A';$this->putJson(self::URL,$this->pedido($d,$s),self::HEADERS)->assertOk();
        app('auth')->forgetGuards();Sanctum::actingAs(Usuario::findOrFail(1)->noVinculo(2));$outro=$this->atual();
        $this->assertNotSame($d['editor']['contexto'],$outro['editor']['contexto']);$this->assertSame(0,$outro['versao']);$this->assertStringNotContainsString('Privado do vínculo A',json_encode($outro));
    }

    public function test_projecao_oculta_widget_revogado_mas_mescla_preserva_para_reentrada():void
    {
        $d=$this->atual();$defs=$d['editor']['catalogo'];$s=$d['editor']['composicao'];$s['widgets']['devolutivas']['title']='Título restrito';
        $reduzido=$defs;$reduzido['widgets']=array_values(array_filter($defs['widgets'],fn($w)=>$w['id']!=='devolutivas'));
        $visivel=ComposicaoPainel::projetar($s,'turmas',$reduzido);
        $this->assertArrayNotHasKey('devolutivas',$visivel['widgets']);$this->assertStringNotContainsString('Título restrito',json_encode($visivel));
        $merged=ComposicaoPainel::mesclar($visivel,$s,$reduzido);$this->assertSame('Título restrito',$merged['widgets']['devolutivas']['title']);
        $retorno=ComposicaoPainel::projetar($merged,'turmas',$defs);$this->assertSame('Título restrito',$retorno['widgets']['devolutivas']['title']);
    }

    public function test_bootstrap_vem_na_consulta_ja_necessaria_e_sem_alterar_contrato_antigo():void
    {
        $this->getJson('/api/v1/turmas')->assertOk()->assertJsonMissingPath('meta.editor_painel');
        $this->getJson('/api/v1/turmas',self::HEADERS)->assertOk()->assertJsonPath('meta.editor_painel.editor.schema',3)->assertJsonPath('meta.editor_painel.versao',0);
        $this->getJson('/api/v1/turmas/1',self::HEADERS)->assertOk()->assertJsonPath('meta.editor_painel.editor.schema',3);
        if($dir=getenv('G360_EDITOR_EXPORT_DIR')){
            if(!is_dir($dir))mkdir($dir,0700,true);
            foreach(['turmas'=>[], 'turma'=>['X-G360-Painel-Turma'=>'1']] as $painel=>$headers){$r=$this->getJson('/api/v1/paineis/preferencias/'.$painel,self::HEADERS+$headers)->assertOk();file_put_contents($dir.'/editor-'.$painel.'.json',json_encode(['data'=>$r->json('data'),'etag'=>$r->headers->get('ETag')],JSON_THROW_ON_ERROR|JSON_PRETTY_PRINT));}
        }
    }

    public function test_cache_tecnico_nao_contem_titulos_e_versao_antiga_nao_se_torna_atual():void
    {
        config(['cache.default'=>'array']);Cache::flush();
        $d=$this->atual();$s=$d['editor']['composicao'];$s['sections'][0]['title']='Nome privado que não pode ir ao cache';$s['sections'][0]['originalTitle']='Outro texto privado';
        $this->putJson(self::URL,$this->pedido($d,$s),self::HEADERS)->assertOk();$novo=$this->atual();
        $key=\App\Support\CacheComposicaoPainel::chave($novo['editor']['contexto'],1,$novo['editor']['catalogo']);
        $raw=Cache::get($key);$this->assertIsArray($raw);$this->assertStringNotContainsString('privado',json_encode($raw));
        foreach($raw['sections'] as $n){$this->assertArrayNotHasKey('title',$n);$this->assertArrayNotHasKey('originalTitle',$n);}
        foreach($raw['widgets'] as $n)$this->assertArrayNotHasKey('title',$n);
        // Simular leitor atrasado que repovoa a chave da versão anterior.
        $oldKey=\App\Support\CacheComposicaoPainel::chave($d['editor']['contexto'],0,$d['editor']['catalogo']);Cache::put($oldKey,$raw,300);
        $this->assertSame(1,$this->atual()['versao']);$this->assertSame($s['sections'][0]['title'],$this->atual()['editor']['composicao']['sections'][0]['title']);
    }

    public function test_etag_revalida_autorizacao_e_muda_apos_salvamento():void
    {
        $r=$this->getJson(self::URL,self::HEADERS)->assertOk();$etag=$r->headers->get('ETag');$this->assertNotEmpty($etag);
        $this->getJson(self::URL,self::HEADERS+['If-None-Match'=>$etag])->assertStatus(304);
        $d=$r->json('data');$s=$d['editor']['composicao'];$s['sections'][0]['title']='Mudou';$this->putJson(self::URL,$this->pedido($d,$s),self::HEADERS)->assertOk();
        $this->getJson(self::URL,self::HEADERS+['If-None-Match'=>$etag])->assertOk()->assertJsonPath('data.versao',1);
        DB::table('usuario_vinculos')->where('id',1)->update(['situacao'=>'encerrado']);
        $this->getJson(self::URL,self::HEADERS+['If-None-Match'=>$etag])->assertForbidden();
    }

    public function test_hit_de_cache_corrompido_e_recalculado_sem_erro500():void
    {
        config(['cache.default'=>'array']);Cache::flush();$d=$this->atual();$key=\App\Support\CacheComposicaoPainel::chave($d['editor']['contexto'],0,$d['editor']['catalogo']);
        foreach(['corrompido',['schema'=>3],['sections'=>[],'widgets'=>[]]] as $bad){Cache::put($key,$bad,300);$atual=$this->atual();$this->assertFalse($atual['editor']['indisponivel']);$this->assertEquals($d['editor']['composicao'],$atual['editor']['composicao']);}
    }

    public function test_promover_pin_dormente_nao_muda_ancora_silenciosamente():void
    {
        $d=$this->atual();$defs=$d['editor']['catalogo'];$s=$d['editor']['composicao'];$s['widgets']['devolutivas']['fixo']=true;
        $reduzido=$defs;$reduzido['widgets']=array_values(array_filter($defs['widgets'],fn($w)=>$w['id']!=='devolutivas'));
        $proposta=ComposicaoPainel::projetar($s,'turmas',$reduzido);$pai=$s['widgets']['devolutivas']['parent'];
        $floor=250;foreach($proposta['widgets'] as &$w)if($w['parent']===$pai){$w['parent']=null;$w['x']=0;$w['y']=$floor;$floor+=$w['h'];}unset($w);
        $proposta['sections']=array_values(array_filter($proposta['sections'],fn($n)=>$n['id']!==$pai));
        $this->expectException(\Illuminate\Validation\ValidationException::class);$this->expectExceptionMessage('posição fixada preservada');ComposicaoPainel::mesclar($proposta,$s,$reduzido);
    }

    public function test_salvar_sem_editar_apos_perda_de_permissao_preserva_container_dormente():void
    {
        $d=$this->atual();$defs=$d['editor']['catalogo'];$s=$d['editor']['composicao'];$id='pessoal-'.Str::uuid();
        $s['sections'][]=['id'=>$id,'kind'=>'section','title'=>'Agrupamento pessoal','originalTitle'=>'Nome original','parent'=>null,'x'=>0,'y'=>350,'w'=>12,'h'=>12,'fixo'=>false];
        $s['widgets']['devolutivas']=array_replace($s['widgets']['devolutivas'],['parent'=>$id,'x'=>0,'y'=>0,'fixo'=>true]);ComposicaoPainel::validar($s,$defs);
        $reduzido=$defs;$reduzido['widgets']=array_values(array_filter($defs['widgets'],fn($w)=>$w['id']!=='devolutivas'));
        $projecao=ComposicaoPainel::projetar($s,'turmas',$reduzido);$this->assertNotContains($id,array_column($projecao['sections'],'id'));
        $merged=ComposicaoPainel::mesclar($projecao,$s,$reduzido);$retorno=ComposicaoPainel::projetar($merged,'turmas',$defs);
        $this->assertSame($s['widgets']['devolutivas'],$retorno['widgets']['devolutivas']);
        $this->assertSame(array_column($s['sections'],null,'id')[$id],array_column($retorno['sections'],null,'id')[$id]);
    }

    public function test_ordem_dos_cards_e_secoes_legados_e_projetada_sem_regravar():void
    {
        $url='/api/v1/paineis/preferencias/turma';$headers=['X-G360-Painel-Turma'=>'1'];$d=$this->getJson($url,$headers)->assertOk()->json('data');
        $ranks=['estudantes'=>[1,2],'periodo-agenda'=>[1,1],'analises'=>[1,0],'planejamento'=>[0,1],'ensino'=>[0,0],'decisoes'=>[0,2]];
        $widgets=$d['padrao']['desktop'];foreach($widgets as &$w){$w['ordem_card']=$ranks[$w['section']][0];$w['ordem_secao']=$ranks[$w['section']][1];}unset($w);
        $this->putJson($url,['versao'=>0,'chave'=>(string)Str::uuid(),'acao'=>'salvar','layouts'=>['desktop'=>$widgets]],$headers)->assertOk();
        $antes=DB::table('preferencias_paineis')->value('desktop');$s=$this->getJson($url,$headers+self::HEADERS)->assertOk()->json('data.editor.composicao');$this->assertIsArray($s);
        $secoes=array_column($s['sections'],null,'id');$this->assertLessThan($secoes['resultados']['y'],$secoes['acompanhamento']['y']);
        $this->assertLessThan($secoes['periodo-agenda']['y'],$secoes['analises']['y']);$this->assertLessThan($secoes['estudantes']['y'],$secoes['periodo-agenda']['y']);
        $this->assertSame($antes,DB::table('preferencias_paineis')->value('desktop'));$this->assertNull(DB::table('preferencias_paineis')->value('composicao'));
    }

    public function test_merge_valida_limite_global_e_profundidade_incluindo_secoes_dormentes():void
    {
        $cat=[];foreach(['devolutivas','proximos-compromissos'] as $id)$cat[]=['id'=>$id,'section'=>'planejamento','desktop'=>['defaultW'=>6,'defaultH'=>9,'minW'=>3,'maxW'=>12,'minH'=>7,'maxH'=>20]];
        $defs=ComposicaoPainel::definicoes('turmas',$cat);$s=ComposicaoPainel::padrao('turmas',$defs);$id='pessoal-'.Str::uuid();
        $section=fn($id,$parent,$y,$h)=>['id'=>$id,'kind'=>'section','title'=>'Seção','originalTitle'=>'Seção','parent'=>$parent,'x'=>0,'y'=>$y,'w'=>12,'h'=>$h,'fixo'=>false];
        $s['sections'][]=$section($id,null,350,20);$s['widgets']['devolutivas']=array_replace($s['widgets']['devolutivas'],['parent'=>$id,'x'=>0,'y'=>0]);
        $reduced=$defs;$reduced['widgets']=array_values(array_filter($defs['widgets'],fn($w)=>$w['id']!=='devolutivas'));
        $proposal=ComposicaoPainel::projetar($s,'turmas',$reduced);
        for($i=0;count($proposal['sections'])<15;$i++)$proposal['sections'][]=$section('pessoal-'.Str::uuid(),null,200+$i*6,6);
        ComposicaoPainel::validar($proposal,$reduced);
        try{ComposicaoPainel::mesclar($proposal,$s,$reduced);$this->fail('16 seções duráveis não podem ser salvas.');}catch(\Illuminate\Validation\ValidationException $e){$this->assertArrayHasKey('composicao',$e->errors());}
        $nested=$s;$nested['sections']=[$section('analises',null,0,100),$section('cadastros',null,150,140),$section($id,'analises',40,20)];
        foreach(['turmas-lista'=>'cadastros','proximos-compromissos'=>'analises','devolutivas'=>$id] as $wid=>$parent)$nested['widgets'][$wid]=array_replace($nested['widgets'][$wid],['parent'=>$parent,'x'=>0,'y'=>0]);
        ComposicaoPainel::validar($nested,$defs);$view=ComposicaoPainel::projetar($nested,'turmas',$reduced);
        foreach($view['sections'] as &$n)if($n['id']==='analises'){$n['parent']='cadastros';$n['y']=30;}unset($n);ComposicaoPainel::validar($view,$reduced);
        try{ComposicaoPainel::mesclar($view,$nested,$reduced);$this->fail('Terceiro nível dormente não pode ser salvo.');}catch(\Illuminate\Validation\ValidationException $e){$this->assertArrayHasKey('composicao',$e->errors());}
        $this->assertDatabaseCount('preferencias_paineis',0);$this->assertDatabaseCount('preferencias_painel_eventos',0);
    }
}
