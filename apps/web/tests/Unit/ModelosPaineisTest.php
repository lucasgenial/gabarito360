<?php

namespace Tests\Unit;

use App\Support\ModelosPaineis;
use PHPUnit\Framework\TestCase;

class ModelosPaineisTest extends TestCase
{
    public function test_modelos_respeitam_permissoes_e_veto_administrativo_mesmo_com_extras(): void
    {
        $extras=['turma.ver','relatorio.turma','aluno.ver','turma.vincular_professor'];
        foreach(['secretario_escolar','auxiliar_secretaria'] as $perfil) {
            foreach(['turma','turmas'] as $pagina) {
                $m=ModelosPaineis::para($pagina,$perfil,$extras);
                $this->assertNotEmpty($m);
                $this->assertSame(['administrativo'],array_values(array_unique(array_column($m,'publico'))));
            }
        }
        $this->assertSame([],ModelosPaineis::para('turma','professor',['turma.ver']));
        $this->assertSame([],ModelosPaineis::para('turma','coordenador',['relatorio.turma']));
        $this->assertSame([],ModelosPaineis::para('invalida','coordenador',$extras));
    }

    public function test_metadados_separam_factory_demonstrativa_de_fontes_operacionais(): void
    {
        $m=ModelosPaineis::para('turma','coordenador',['turma.ver','relatorio.turma','turma.vincular_professor']);
        $this->assertNotEmpty($m);
        $this->assertCount(count($m),array_unique(array_column($m,'id')));
        foreach($m as $item) {
            foreach(['titulo','finalidade','permissao','pagina','posicao','tamanho','celular','fonte','dependencia','ativacao','filtros','visual','funcional'] as $campo) $this->assertArrayHasKey($campo,$item);
            $this->assertSame('demonstrativo',$item['estado']);
            $this->assertSame('Em breve',$item['selo']);
            foreach(['url','endpoint','valor','eventos','alunos','percentual'] as $campo) $this->assertArrayNotHasKey($campo,$item);
        }
        $this->assertContains('funcional',ModelosPaineis::ESTADOS);
        $this->assertContains('zero',ModelosPaineis::ESTADOS);
        $this->assertNotContains('sem_permissao',ModelosPaineis::ESTADOS);
    }
}
