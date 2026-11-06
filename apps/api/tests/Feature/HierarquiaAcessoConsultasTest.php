<?php
namespace Tests\Feature;

use App\Models\Usuario;
use App\Support\{ContextoVinculo,ConsultasPedagogicas,GruposFuncionais};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

/** Revisor independente: equivalência com o algoritmo anterior, sem autoridade memoizada. */
final class HierarquiaAcessoConsultasTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();config(['espacos.ativo'=>true,'espacos.pessoal'=>true]);
        $this->travelTo(now()->startOfSecond());
        foreach([1,2] as $id){
            DB::table('usuarios')->insert(['id'=>$id,'perfil'=>'admin_rede','nome'=>'Pessoa fictícia','email'=>$id.'@example.test',
                'cpf'=>str_pad((string)$id,11,'0',STR_PAD_LEFT),'password'=>'ficticio','ativo'=>true]);
            DB::table('clientes')->insert(['id'=>$id,'tipo'=>'rede','nome'=>'Cliente fictício','modalidade'=>'interno','situacao'=>'ativo']);
            DB::table('redes')->insert(['id'=>$id,'nome'=>'Rede fictícia','tipo'=>'estadual','uf'=>'BA']);
            DB::table('nucleos')->insert(['id'=>$id,'rede_id'=>$id,'nome'=>'Núcleo fictício']);
            DB::table('escolas')->insert(['id'=>$id,'nucleo_id'=>$id,'nome'=>'Escola fictícia','inep'=>'0000000'.$id,'tipo_rede'=>'estadual']);
            foreach(['rede'=>0,'nucleo'=>2,'escola'=>4] as $tipo=>$offset)
                DB::table('espacos')->insert(['id'=>$id+$offset,'cliente_id'=>$id,'tipo'=>$tipo,'nome'=>'Espaço fictício','situacao'=>'ativo',$tipo.'_id'=>$id]);
            DB::table('turmas')->insert(['id'=>$id,'escola_id'=>$id,'espaco_id'=>$id+4,'nome'=>'3A','serie'=>'3','turno'=>'tarde','ano_letivo'=>2026]);
        }
        DB::table('espacos')->insert(['id'=>7,'cliente_id'=>1,'tipo'=>'pessoal','nome'=>'Pessoal fictício','situacao'=>'ativo','usuario_proprietario_id'=>1]);
        DB::table('usuario_vinculos')->insert(['id'=>1,'usuario_id'=>1,'espaco_id'=>5,'perfil'=>'professor','situacao'=>'ativo','inicio_em'=>now()->subDay()]);
        DB::table('vinculo_turmas')->insert(['vinculo_id'=>1,'espaco_id'=>5,'turma_id'=>1]);
    }

    /** Oráculo anterior à refatoração; não chama o novo helper nem seus builders. */
    private function anterior(object $e): bool
    {
        if(!in_array($e->tipo,['rede','nucleo','escola','pessoal'],true)||$e->situacao!=='ativo')return false;
        $ref=$e->tipo==='pessoal'?'usuario_proprietario_id':$e->tipo.'_id';
        foreach(['rede_id','nucleo_id','escola_id','usuario_proprietario_id'] as $c)
            if(($c===$ref)!==($e->$c!==null))return false;
        if(!DB::table('clientes')->where('id',$e->cliente_id)->where('situacao','ativo')->exists())return false;
        if($e->tipo==='pessoal')return DB::table('usuarios')->where('id',$e->usuario_proprietario_id)->where('ativo',true)->exists();
        $t=match($e->tipo){'rede'=>'redes','nucleo'=>'nucleos','escola'=>'escolas'};
        $i=DB::table($t)->find($e->{$e->tipo.'_id'});if(!$i)return false;if($e->tipo==='rede')return true;
        $tp=$e->tipo==='escola'?'nucleo':'rede';$pai=DB::table('espacos')->where('tipo',$tp)->where($tp.'_id',$i->{$tp.'_id'})->first();
        return $pai&&(int)$pai->cliente_id===(int)$e->cliente_id&&$this->anterior($pai);
    }

    private function comparar(object $e,bool $esperado): void
    {
        $this->assertSame($esperado,$this->anterior($e),'Contrato anterior inesperado.');
        $this->assertSame($esperado,ContextoVinculo::espacoValido($e),'Refatoração divergiu.');
    }

    public function test_todos_tipos_validos_usam_uma_statement_atual_sem_memoizar(): void
    {
        foreach([1,3,5,7] as $id){
            $e=DB::table('espacos')->find($id);$this->comparar($e,true);
            DB::flushQueryLog();DB::enableQueryLog();
            try{$this->assertTrue(ContextoVinculo::espacoValido($e));$this->assertCount(1,DB::getQueryLog());}
            finally{DB::disableQueryLog();}
        }
    }

    public function test_todas_combinacoes_de_referencias_malformadas_sao_equivalentes(): void
    {
        foreach(['rede','nucleo','escola','pessoal'] as $tipo){
            for($mask=0;$mask<16;$mask++){
                $e=DB::table('espacos')->find(match($tipo){'rede'=>1,'nucleo'=>3,'escola'=>5,'pessoal'=>7});
                foreach(['rede_id','nucleo_id','escola_id','usuario_proprietario_id'] as $i=>$c)$e->$c=($mask&(1<<$i))?1:null;
                $valida=$mask===(1<<array_search($tipo,['rede','nucleo','escola','pessoal'],true));
                $this->comparar($e,$valida);
            }
        }
        $e=DB::table('espacos')->find(5);$e->tipo='secretaria';$this->comparar($e,false);
    }

    public static function alteracoes(): array
    {
        return [
            ['espacos',5,'situacao','suspenso'],['espacos',3,'situacao','suspenso'],['espacos',1,'situacao','encerrado'],
            ['espacos',3,'cliente_id',2],['espacos',1,'cliente_id',2],['clientes',1,'situacao','encerrado'],
            ['escolas',1,'nucleo_id',2],['nucleos',1,'rede_id',2],
        ];
    }

    #[DataProvider('alteracoes')]
    public function test_releitura_apos_suspensao_ou_reparentamento_recusa_sem_cache(string $t,int $id,string $campo,mixed $valor): void
    {
        $this->comparar(DB::table('espacos')->find(5),true);
        DB::table($t)->where('id',$id)->update([$campo=>$valor]);
        $this->comparar(DB::table('espacos')->find(5),false);
    }

    public function test_pessoal_revalida_proprietario_e_cliente_sem_cache(): void
    {
        $e=DB::table('espacos')->find(7);$this->comparar($e,true);
        DB::table('usuarios')->where('id',1)->update(['ativo'=>false]);$this->comparar($e,false);
        DB::table('usuarios')->where('id',1)->update(['ativo'=>true]);$this->comparar($e,true);
        DB::table('clientes')->where('id',1)->update(['situacao'=>'encerrado']);$this->comparar($e,false);
        $e->usuario_proprietario_id=999999;$this->comparar($e,false);
    }

    public function test_referencias_institucionais_inexistentes_e_tipo_errado_do_pai_recusam(): void
    {
        foreach([1,3,5] as $id){$e=DB::table('espacos')->find($id);$e->{$e->tipo.'_id'}=999999;$this->comparar($e,false);}
        DB::table('espacos')->where('id',3)->update(['tipo'=>'pessoal']);
        $this->comparar(DB::table('espacos')->find(5),false);
    }

    public function test_mesmo_ator_e_instancia_nao_conservam_turma_revogada(): void
    {
        $u=Usuario::findOrFail(1)->noVinculo(1);$q=new ConsultasPedagogicas($u);
        $this->assertSame([1],$q->tabela('turmas')->pluck('turmas.id')->all());
        DB::table('vinculo_turmas')->where('vinculo_id',1)->delete();
        $this->assertSame([],$q->tabela('turmas')->pluck('turmas.id')->all());
        $this->assertSame('ativo',DB::table('usuario_vinculos')->where('id',1)->value('situacao'));
        DB::table('espacos')->where('id',1)->update(['situacao'=>'suspenso']);
        $this->recusa(fn()=>$q->tabela('turmas')->get(),403);
    }

    private function recusa(callable $f,int $status=422): void
    {
        try{$f();$this->fail('Conflito/revogação deveria ser recusado.');}
        catch(HttpException $e){$this->assertSame($status,$e->getStatusCode());}
    }

    public static function intervalos(): array
    {
        return [
            ['ativo',null,-1,null,false],['convidado',null,-1,null,false],['suspenso',null,-1,null,false],
            ['encerrado',null,-1,null,true],['ativo',0,-1,null,true],['ativo',1,-1,null,false],
            ['ativo',null,2,1,true],['ativo',null,1,1,true],['ativo',null,0,1,false],
        ];
    }

    #[DataProvider('intervalos')]
    public function test_conflitos_preservam_estados_e_intervalos_abertos(string $estado,?int $fimExistente,int $inicioExistente,?int $fimNovo,bool $permitir): void
    {
        DB::table('usuario_vinculos')->where('id',1)->update(['situacao'=>$estado,
            'inicio_em'=>now()->addDays($inicioExistente),'fim_em'=>$fimExistente===null?null:now()->addDays($fimExistente)]);
        $f=fn()=>GruposFuncionais::conferir(1,5,'secretario_escolar',now(),$fimNovo===null?null:now()->addDays($fimNovo));
        if($permitir){$f();$this->addToAssertionCount(1);}else $this->recusa($f);
    }

    public function test_excecao_revogada_e_escopo_outra_escola_nao_se_confundem(): void
    {
        $f=fn()=>GruposFuncionais::conferir(1,5,'secretario_escolar');$this->recusa($f);
        DB::table('usuario_funcoes_excecoes')->insert(['usuario_id'=>1,'espaco_id'=>6,'permitir'=>true,'versao'=>1,
            'chamado'=>'QA-FICTICIO','motivo'=>'Exceção fictícia','definida_por'=>2]);$this->recusa($f);
        DB::table('usuario_funcoes_excecoes')->insert(['usuario_id'=>1,'espaco_id'=>5,'permitir'=>true,'versao'=>1,
            'chamado'=>'QA-FICTICIO','motivo'=>'Exceção fictícia','definida_por'=>2]);$f();$this->addToAssertionCount(1);
        DB::table('usuario_funcoes_excecoes')->where('espaco_id',5)->update(['permitir'=>false]);$this->recusa($f);
        GruposFuncionais::conferir(1,6,'secretario_escolar');
        GruposFuncionais::conferir(1,5,'secretario_escolar',null,null,1);
        GruposFuncionais::conferir(1,5,'aluno');$this->addToAssertionCount(3);
    }
    public function test_pais_com_referencia_adicional_ou_ausente_nao_sao_aceitos_por_exists(): void
    {
        $e=DB::table('espacos')->find(5);$this->comparar($e,true);
        DB::table('espacos')->where('id',3)->update(['usuario_proprietario_id'=>2]);$this->comparar($e,false);
        DB::table('espacos')->where('id',3)->update(['usuario_proprietario_id'=>null]);$this->comparar($e,true);
        DB::table('espacos')->where('id',1)->update(['usuario_proprietario_id'=>2]);$this->comparar($e,false);
        DB::table('espacos')->where('id',1)->update(['usuario_proprietario_id'=>null]);$this->comparar($e,true);
        DB::table('espacos')->where('id',3)->delete();$this->comparar($e,false);
    }

    public function test_rede_e_nucleo_nao_pulam_cliente_suspenso_nem_instituicao_inexistente(): void
    {
        foreach([1,3] as $id){$e=DB::table('espacos')->find($id);$this->comparar($e,true);}
        DB::table('clientes')->where('id',1)->update(['situacao'=>'encerrado']);
        foreach([1,3] as $id)$this->comparar(DB::table('espacos')->find($id),false);
    }

}
