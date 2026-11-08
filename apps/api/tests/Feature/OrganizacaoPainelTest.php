<?php
namespace Tests\Feature;

use App\Models\Usuario;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class OrganizacaoPainelTest extends TestCase
{
    use \Tests\Support\BancoCurriculoDescartavel, \Tests\Support\CenarioDisciplinas;

    private const URL='/api/v1/paineis/preferencias/turma';
    private const HEADERS=['X-G360-Painel-Turma'=>'1'];

    private function widgets(): array
    {
        $w=static fn($id,$section,$x,$card,$secao)=>compact('id','section','x')+['y'=>0,'w'=>6,'h'=>14,'ordem_card'=>$card,'ordem_secao'=>$secao];
        return [
            $w('alunos-destaques','estudantes',0,1,2)+['fixo'=>true],
            $w('distribuicao-acertos','analises',0,1,0),
            $w('periodos-escolares','periodo-agenda',0,1,1)+['orientacao'=>'horizontal'],
            $w('agenda-pedagogica','periodo-agenda',6,1,1),
            $w('metas','decisoes',0,0,0),
        ];
    }

    private function pedido(array $widgets,int $versao=0): array
    {
        return ['versao'=>$versao,'chave'=>(string)Str::uuid(),'acao'=>'salvar','layouts'=>['desktop'=>$widgets]];
    }

    public function test_ordem_e_geometria_sao_atomicas_versionadas_e_isoladas_por_vinculo(): void
    {
        $padrao=$this->getJson(self::URL,self::HEADERS)->assertOk()->json('data');
        if(getenv('G360_HIERARQUIA_EXPORT')){
            $dir=sys_get_temp_dir().'/g360-hierarquia-render';if(!is_dir($dir))mkdir($dir,0700,true);
            file_put_contents($dir.'/preferencias.json',json_encode($padrao,JSON_THROW_ON_ERROR));
        }
        $fixture=json_decode(file_get_contents(base_path('tests/Fixtures/organizacao-painel.json')),true,512,JSON_THROW_ON_ERROR);
        $this->assertSame($fixture,array_intersect_key($padrao,array_flip(['catalogo','padrao'])));
        $porId=array_column($padrao['catalogo'],null,'id');
        foreach(['periodos-escolares','agenda-pedagogica'] as $id)$this->assertSame('periodo-agenda',$porId[$id]['section']);
        $p=$this->pedido($this->widgets());
        $this->putJson(self::URL,$p,self::HEADERS)->assertOk()->assertJsonPath('data.versao',1)
            ->assertJsonPath('data.layouts.desktop.0.ordem_card',1)->assertJsonPath('data.layouts.desktop.0.fixo',true)
            ->assertJsonPath('data.layouts.desktop.2.orientacao','horizontal');
        $this->putJson(self::URL,$p,self::HEADERS)->assertOk()->assertJsonPath('data.repetido',true)->assertJsonPath('data.versao',1);
        $this->putJson(self::URL,$this->pedido(array_reverse($this->widgets())),self::HEADERS)->assertConflict();
        $this->assertDatabaseCount('preferencias_painel_eventos',1);
        app('auth')->forgetGuards();Sanctum::actingAs(Usuario::findOrFail(1)->noVinculo(4));
        $this->getJson(self::URL,self::HEADERS)->assertOk()->assertJsonPath('data.versao',0)->assertJsonCount(0,'data.layouts.desktop');
        $this->assertDatabaseCount('preferencias_paineis',1);
    }

    public function test_grupo_anterior_replay_e_cliente_sem_metadados_preservam_historico_e_ordem(): void
    {
        $legado=$this->widgets();foreach($legado as &$w)if(in_array($w['id'],['periodos-escolares','agenda-pedagogica']))$w['section']='planejamento';unset($w);
        $legado=array_map(static fn($w)=>array_diff_key($w,array_flip(['ordem_card','ordem_secao'])),$legado);
        $p=$this->pedido($legado);
        // Fixture anterior à release: inserir o evento uma única vez; os triggers imutáveis continuam ativos.
        $contexto=['usuario_id'=>1,'vinculo_id'=>1,'espaco_id'=>5,'perfil'=>'coordenador','recurso_tipo'=>'turma','recurso_id'=>1];
        $json=json_encode($legado,JSON_THROW_ON_ERROR);
        $id=DB::table('preferencias_paineis')->insertGetId($contexto+['contexto_hash'=>hash('sha256',json_encode($contexto,JSON_THROW_ON_ERROR)),'painel'=>'turma','versao'=>1,'apresentacao'=>'tabela','desktop'=>$json,'mobile'=>'[]','atualizado_em'=>now()]);
        $canonico=function($v)use(&$canonico){if(!is_array($v))return $v;if(!array_is_list($v))ksort($v,SORT_STRING);return array_map($canonico,$v);};
        $evento=['versao'=>1,'apresentacao'=>'tabela','layouts'=>['desktop'=>$legado,'mobile'=>[]]];
        DB::table('preferencias_painel_eventos')->insert(['preferencia_id'=>$id,'chave'=>$p['chave'],'pedido_hash'=>hash('sha256',json_encode($canonico($p),JSON_THROW_ON_ERROR)),'acao'=>'salvar','versao'=>1,'resultado'=>json_encode($evento,JSON_THROW_ON_ERROR),'ocorrido_em'=>now()]);
        $eventoAntes=DB::table('preferencias_painel_eventos')->value('resultado');
        $this->getJson(self::URL,self::HEADERS)->assertOk()->assertJsonPath('data.layouts.desktop.2.section','periodo-agenda');
        $this->putJson(self::URL,$p,self::HEADERS)->assertOk()->assertJsonPath('data.repetido',true)->assertJsonPath('data.layouts.desktop.2.section','periodo-agenda');
        $this->assertSame($json,DB::table('preferencias_paineis')->value('desktop'));
        $this->assertSame($eventoAntes,DB::table('preferencias_painel_eventos')->value('resultado'));
        $this->putJson(self::URL,$this->pedido($this->widgets(),1),self::HEADERS)->assertOk()->assertJsonPath('data.versao',2);
        $this->putJson(self::URL,$this->pedido($legado,2),self::HEADERS)->assertOk()->assertJsonPath('data.versao',3)
            ->assertJsonPath('data.layouts.desktop.0.ordem_secao',2)->assertJsonPath('data.layouts.desktop.4.ordem_card',0);
        $this->assertDatabaseCount('preferencias_painel_eventos',3);$this->assertDatabaseCount('provas',2);
        $this->assertSame($eventoAntes,DB::table('preferencias_painel_eventos')->orderBy('id')->value('resultado'));
    }

    public function test_ordens_invalidas_ambiguas_e_secao_livre_nao_gravam_preferencias(): void
    {
        $variacoes=[];
        foreach(['-1'=>-1,'grande'=>64,'texto'=>'1'] as $valor){$w=$this->widgets();$w[0]['ordem_card']=$valor;$variacoes[]=$w;}
        $w=$this->widgets();unset($w[0]['ordem_card']);$variacoes[]=$w;
        $w=$this->widgets();$w[3]['ordem_secao']=2;$variacoes[]=$w;
        $w=$this->widgets();$w[4]['ordem_card']=1;$variacoes[]=$w;
        $w=$this->widgets();$w[1]['ordem_secao']=2;$variacoes[]=$w;
        $w=$this->widgets();$w[2]['section']='estudantes';$variacoes[]=$w;
        foreach($variacoes as $widgets)$this->putJson(self::URL,$this->pedido($widgets),self::HEADERS)->assertUnprocessable();
        $this->assertDatabaseCount('preferencias_paineis',0);$this->assertDatabaseCount('preferencias_painel_eventos',0);
    }
}
