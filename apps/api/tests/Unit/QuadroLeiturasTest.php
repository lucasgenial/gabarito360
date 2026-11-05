<?php

namespace Tests\Unit;

use App\Services\Omr\QuadroLeituras;
use PHPUnit\Framework\TestCase;

class QuadroLeiturasTest extends TestCase
{
    public function test_preserva_diferenca_omr_revisao_humana_mesmo_quando_alternativa_nao_muda(): void
    {
        $r = ['id' => 8, 'numero_questao' => 1, 'alternativa' => 'A', 'ambigua' => false];
        $log = ['id' => 9, 'cartao_resposta_id' => 8, 'usuario_id' => 5, 'created_at' => '2026-11-05 12:00:00'];
        $inicial = ['leituras_omr' => [$r], 'respostas' => [$r]];
        $e = ['acao' => 'revisada', 'motivo' => 'Conferência da questão 1', 'snapshot' => ['respostas' => [$r], 'revisoes' => [$log], 'ator_registrado' => ['id' => 5, 'nome' => 'Revisor fictício']]];
        $linhas = (new QuadroLeituras)->montar($inicial, [$e, $e]);
        $this->assertCount(1, $linhas);
        $this->assertSame('A', $linhas[0]['leitura']);
        $this->assertSame('A', $linhas[0]['resposta']);
        $this->assertSame(1, substr_count($linhas[0]['observacao'], 'Revisão humana'));
        $this->assertStringContainsString('Revisor fictício em 05/11/2026 09:00:00 -03:00', $linhas[0]['observacao']);
    }

    public function test_registro_anterior_nao_e_apresentado_como_leitura_automatica_original(): void
    {
        $r = ['id' => 8, 'numero_questao' => 1, 'alternativa' => 'B'];
        $linhas = (new QuadroLeituras)->montar(['respostas' => [$r]], [['acao' => 'registro_anterior', 'snapshot' => []]]);
        $this->assertSame('Leitura automática original não preservada', $linhas[0]['leitura']);
        $this->assertSame('Sem registro preservado de intervenção', $linhas[0]['observacao']);
    }

    public function test_proposta_ainda_nao_aplicada_nao_inventa_resposta_registrada(): void
    {
        $linhas = (new QuadroLeituras)->montar(['leituras_propostas' => [['numero_questao' => 1, 'alternativa' => 'C']]], []);
        $this->assertSame('C', $linhas[0]['leitura']);
        $this->assertSame('Ainda não aplicada', $linhas[0]['resposta']);
    }

    public function test_cores_usam_somente_referencia_preservada_sem_percentuais_no_historico(): void
    {
        $r = ['numero_questao'=>1,'alternativa'=>'A'];
        $final = ['numero_questao'=>1,'alternativa'=>'B'];
        $d = ['leituras_omr'=>[$r],'respostas'=>[$r], 'referencia_gabarito'=>['questoes'=>[['numero_questao'=>1,'esperada'=>'B','anulada'=>false]]]];
        $linha = (new QuadroLeituras)->montar($d, [['snapshot'=>['respostas'=>[$final]]]])[0];
        $this->assertSame('incorreta',$linha['leitura_situacao']);
        $this->assertSame('correta',$linha['situacao']);
        $this->assertTrue($linha['modificada']);
        $this->assertTrue($linha['referencia_preservada']);
        $this->assertArrayNotHasKey('turma',$linha);
        $this->assertArrayNotHasKey('geral',$linha);
        unset($d['referencia_gabarito']);
        $linha = (new QuadroLeituras)->montar($d, [])[0];
        $this->assertSame('nao_preservada',$linha['situacao']);
        $this->assertFalse($linha['referencia_preservada']);
    }
}
