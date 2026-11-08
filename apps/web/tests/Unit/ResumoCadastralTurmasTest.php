<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Support\ResumoCadastralTurmas;
use PHPUnit\Framework\TestCase;

class ResumoCadastralTurmasTest extends TestCase
{
    private function turma(int $id, mixed $ativo = true, mixed $total = 0): array
    {
        return ['id'=>$id, 'ativo'=>$ativo, 'total_alunos'=>$total];
    }

    public function test_conta_recorte_completo_sem_usar_notas_ou_kpis_de_outro_universo(): void
    {
        $dados = [$this->turma(14, true, 17) + ['media_turma'=>999], $this->turma(25, false, 3),
            $this->turma(33, 1, 0), $this->turma(70, 0, 5)];
        $resumo = ResumoCadastralTurmas::montar($dados, true, true);
        $this->assertSame(['estado'=>'funcional', 'base'=>'colecao_completa_no_recorte',
            'turmas'=>4, 'em_uso'=>2, 'arquivadas'=>2, 'cadastros_ativos'=>25], $resumo);
        $this->assertSame($resumo['turmas'], $resumo['em_uso'] + $resumo['arquivadas']);
        // Novo recorte: não conserva os totais da consulta anterior.
        $outro = ResumoCadastralTurmas::montar([$dados[1]], true, true);
        $this->assertSame(1, $outro['turmas']);
        $this->assertSame(3, $outro['cadastros_ativos']);
    }

    public function test_vazio_completo_e_zero_cadastral_sao_dados_validos(): void
    {
        $vazio = ResumoCadastralTurmas::montar([], true, true);
        $this->assertSame('vazio', $vazio['estado']);
        foreach (['turmas','em_uso','arquivadas','cadastros_ativos'] as $chave) $this->assertSame(0, $vazio[$chave]);
        $comTurma = ResumoCadastralTurmas::montar([$this->turma(1)], true, true);
        $this->assertSame('funcional', $comTurma['estado']);
        $this->assertSame(0, $comTurma['cadastros_ativos']);
    }

    public function test_falha_lista_parcial_e_completude_desconhecida_nao_viram_zero(): void
    {
        foreach ([[], [$this->turma(1, true, 8)]] as $turmas) {
            foreach ([[false,true,'erro'],[true,false,'parcial'],[true,null,'parcial']] as [$sucesso,$completa,$estado]) {
                $resumo = ResumoCadastralTurmas::montar($turmas, $sucesso, $completa);
                $this->assertSame($estado, $resumo['estado']);
                $this->assertSemTotais($resumo);
            }
        }
    }

    public function test_formato_invalido_ou_identidade_duplicada_nao_inventam_total_da_colecao(): void
    {
        foreach ([null,false,0,'',new \stdClass(),['turmas'=>[]],[null],['linha'],
            [['ativo'=>true,'total_alunos'=>1]], [$this->turma(0)],
            [$this->turma(1),$this->turma(1)]] as $turmas) {
            $resumo = ResumoCadastralTurmas::montar($turmas, true, true);
            $this->assertSame('erro', $resumo['estado']);
            $this->assertSemTotais($resumo);
        }
    }

    public function test_contagem_invalida_nao_vira_zero_nem_prejudica_estados_validos(): void
    {
        foreach ([null,-1,true,false,'0','1',1.0,[],new \stdClass()] as $total) {
            $resumo = ResumoCadastralTurmas::montar([$this->turma(1,true,4),$this->turma(2,false,$total)], true, true);
            $this->assertSame('parcial', $resumo['estado']);
            $this->assertSame(2, $resumo['turmas']);
            $this->assertSame(1, $resumo['em_uso']);
            $this->assertSame(1, $resumo['arquivadas']);
            $this->assertNull($resumo['cadastros_ativos']);
        }
        $resumo = ResumoCadastralTurmas::montar([['id'=>1,'ativo'=>true]], true, true);
        $this->assertNull($resumo['cadastros_ativos']);
        $this->assertSame('parcial', $resumo['estado']);
    }

    public function test_estado_desconhecido_nao_e_arquivado_por_coercao(): void
    {
        foreach ([null,'0','1',2,-1,0.0,1.0,[],new \stdClass()] as $ativo) {
            $resumo = ResumoCadastralTurmas::montar([$this->turma(1,true,4),$this->turma(2,$ativo,3)], true, true);
            $this->assertSame('parcial', $resumo['estado']);
            $this->assertSame(2, $resumo['turmas']);
            $this->assertSame(7, $resumo['cadastros_ativos']);
            $this->assertNull($resumo['em_uso']);
            $this->assertNull($resumo['arquivadas']);
        }
    }

    public function test_soma_que_excede_inteiro_nao_retorna_float_como_contagem(): void
    {
        $resumo = ResumoCadastralTurmas::montar([$this->turma(1,true,PHP_INT_MAX),$this->turma(2,true,1)], true, true);
        $this->assertSame('parcial', $resumo['estado']);
        $this->assertNull($resumo['cadastros_ativos']);
        $this->assertSame(2, $resumo['turmas']);
    }

    public function test_agregado_autorizado_preserva_contrato_legado_e_recusa_incoerencia_ou_pagina_parcial(): void
    {
        $turmas = [$this->turma(1,true,4),$this->turma(2,false,3)];
        $agregado = ['versao'=>1,'base'=>'colecao_completa_no_recorte','colecao_completa'=>true,
            'turmas'=>2,'em_uso'=>1,'arquivadas'=>1,'cadastros_ativos'=>7];
        $esperado = ResumoCadastralTurmas::montar($turmas,true,true);
        $this->assertSame($esperado,ResumoCadastralTurmas::daApi($turmas,true,null,false));
        $this->assertSame($esperado,ResumoCadastralTurmas::daApi($turmas,true,$agregado,true));
        foreach ([null,[], $agregado+['extra'=>'permitido']] as $valor) {
            if (is_array($valor) && isset($valor['extra'])) {
                $this->assertSame($esperado,ResumoCadastralTurmas::daApi($turmas,true,$valor,true));
                continue;
            }
            $this->assertSemTotais(ResumoCadastralTurmas::daApi($turmas,true,$valor,true));
        }
        foreach (['versao'=>2,'base'=>'outra','colecao_completa'=>false,'turmas'=>3,
            'em_uso'=>2,'arquivadas'=>0,'cadastros_ativos'=>999] as $campo=>$valor) {
            $r=ResumoCadastralTurmas::daApi($turmas,true,array_replace($agregado,[$campo=>$valor]),true);
            $this->assertSame('erro',$r['estado']);$this->assertSemTotais($r);
        }
        foreach ([true,'7',7.0,-1] as $valor) $this->assertSemTotais(ResumoCadastralTurmas::daApi(
            $turmas,true,array_replace($agregado,['cadastros_ativos'=>$valor]),true));
        // Um total individual ausente não invalida um agregado canônico completo e coerente.
        $turmas[0]['total_alunos']=null;
        $this->assertSame($esperado,ResumoCadastralTurmas::daApi($turmas,true,$agregado,true));
        $this->assertSemTotais(ResumoCadastralTurmas::daApi($turmas,false,$agregado,true));
    }

    private function assertSemTotais(array $resumo): void
    {
        foreach (['turmas','em_uso','arquivadas','cadastros_ativos'] as $chave) $this->assertNull($resumo[$chave]);
    }
}
