<?php
namespace Tests\Unit;

use App\Support\ContratoPreferenciasPainel;
use PHPUnit\Framework\TestCase;

class ContratoPreferenciasPainelTest extends TestCase
{
    private function resposta(): array
    {
        $b=['minW'=>2,'maxW'=>12,'minH'=>4,'maxH'=>8,'defaultW'=>3,'defaultH'=>4];
        return ['versao'=>2,'apresentacao'=>'tabela','repetido'=>false,
            'catalogo'=>[['id'=>'kpi-turmas','section'=>'resumo','desktop'=>$b,'mobile'=>array_replace($b,['minW'=>12,'maxW'=>12,'defaultW'=>12])]],
            'layouts'=>['desktop'=>[['id'=>'kpi-turmas','section'=>'resumo','x'=>0,'y'=>0,'w'=>12,'h'=>7,'altura_desejada'=>4]],'mobile'=>[]]];
    }

    public function test_preserva_demanda_individual_sem_publicar_metadados_extras(): void
    {
        $d=$this->resposta();$d['layouts']['desktop'][0]['conteudo_privado']='não encaminhar';
        $d['catalogo'][0]['desktop']['diagnostico']='não encaminhar';
        $this->assertTrue(ContratoPreferenciasPainel::valido($d));
        $r=ContratoPreferenciasPainel::projetar($d);
        $this->assertSame(4,$r['layouts']['desktop'][0]['altura_desejada']);
        $this->assertSame(7,$r['layouts']['desktop'][0]['h']);
        $this->assertArrayNotHasKey('conteudo_privado',$r['layouts']['desktop'][0]);
        $this->assertArrayNotHasKey('diagnostico',$r['catalogo'][0]['desktop']);
        unset($d['layouts']['desktop'][0]['altura_desejada']);
        $this->assertTrue(ContratoPreferenciasPainel::valido($d));
        $this->assertArrayNotHasKey('altura_desejada',ContratoPreferenciasPainel::projetar($d)['layouts']['desktop'][0]);
    }

    public function test_demanda_invalida_nao_e_coagida_nem_maior_que_a_altura_efetiva(): void
    {
        foreach([3,8,4.0,'4',null,false,[]] as $valor){
            $d=$this->resposta();$d['layouts']['desktop'][0]['altura_desejada']=$valor;
            $this->assertFalse(ContratoPreferenciasPainel::valido($d));
        }
    }

    public function test_padrao_autorizado_e_origem_sao_validados_e_projetados_sem_diagnostico(): void
    {
        $d=$this->resposta();$d['padrao']=['desktop'=>$d['layouts']['desktop'],'diagnostico'=>'privado'];
        $d['origem_disposicao']='perfil';$d['padrao']['desktop'][0]['privado']='não publicar';
        $this->assertTrue(ContratoPreferenciasPainel::valido($d));
        $r=ContratoPreferenciasPainel::projetar($d);
        $this->assertSame('perfil',$r['origem_disposicao']);
        $this->assertArrayNotHasKey('diagnostico',$r['padrao']);
        $this->assertArrayNotHasKey('privado',$r['padrao']['desktop'][0]);
        $d['padrao']['desktop'][0]['id']='componente-fora-do-catalogo';
        $this->assertFalse(ContratoPreferenciasPainel::valido($d));
        $d=$this->resposta();$d['origem_disposicao']='origem-desconhecida';
        $this->assertFalse(ContratoPreferenciasPainel::valido($d));
    }
}
