<?php
namespace Tests\Unit;

use App\Support\IndicadoresTurma;
use PHPUnit\Framework\TestCase;

final class IndicadoresTurmaTest extends TestCase
{
    public function test_base_zero_confirmada_nao_se_confunde_com_ausencia_ou_incoerencia(): void
    {
        $this->assertSame('Base: 0 de 0 resultados; 0 sem percentual válido.',IndicadoresTurma::base(0,0,0));
        $this->assertSame('Base: 2 de 3 resultados; 1 sem percentual válido.',IndicadoresTurma::base(2,3,1));
        foreach([[null,0,0],[0,null,0],[0,0,null],['2',3,1],[3,2,0],[-1,1,2]] as $base){
            $this->assertStringContainsString('não foram informadas',IndicadoresTurma::base(...$base));
        }
    }

    public function test_metadados_preservam_bases_distintas_e_momento_da_consulta_sem_nomes(): void
    {
        $d=['kpis'=>['resultados'=>3],'situacoes_resultados'=>['total'=>3],'analise'=>[
            'resumo'=>['n'=>2],'distribuicao_acertos'=>['percentuais_excluidos'=>1],
            'por_avaliacao'=>[['titulo'=>'Avaliação fictícia','n'=>0,'resultados_total'=>1,'percentuais_excluidos'=>1]],
            'alunos'=>['atencao'=>['items'=>[['nome'=>'Pessoa que não pode aparecer nos metadados']]]],
        ]];
        $a=IndicadoresTurma::ajudas($d,'08/11/2026 10:00:00 -03:00');
        $this->assertStringContainsString('Base: 2 de 3',implode(' ',$a['kpi-media']));
        $this->assertStringContainsString('Base: 3 resultados',implode(' ',$a['situacoes-registradas']));
        $this->assertStringContainsString('Base: 0 de 1',implode(' ',$a['resultados-avaliacao']));
        $texto=json_encode($a,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);
        $this->assertStringContainsString('Consultado em 08/11/2026 10:00:00 -03:00',$texto);
        $this->assertStringNotContainsString('Pessoa que não pode aparecer',$texto);
        $this->assertStringContainsString('Cadastros distintos',implode(' ',$a['kpi-alunos-resultado']));
        $erro=IndicadoresTurma::ajudas([],null,true);
        $this->assertStringContainsString('Nenhum valor foi confirmado',implode(' ',$erro['kpi-media']));
    }
}
