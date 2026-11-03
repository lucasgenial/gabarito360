<?php

namespace Tests\Unit;

use App\Support\EstadoPedidoTitular as Estado;
use DomainException;
use PHPUnit\Framework\TestCase;

class EstadoPedidoTitularTest extends TestCase
{
    public function test_fluxo_com_informacao_adicional_exige_nova_verificacao(): void
    {
        $estado = Estado::Recebida->transicionar(Estado::EmVerificacao);
        $estado = $estado->transicionar(Estado::EmAnalise, true, true);
        $estado = $estado->transicionar(Estado::AguardandoInformacao);
        $estado = $estado->transicionar(Estado::EmVerificacao);
        $estado = $estado->transicionar(Estado::EmAnalise, true, true);
        $this->assertSame(Estado::Concluida, $estado->transicionar(Estado::Concluida, true, true, 'atendido'));
    }

    public function test_recusa_atalhos_e_reabertura_de_estados_terminais(): void
    {
        foreach ([
            [Estado::Recebida, Estado::Concluida],
            [Estado::Recebida, Estado::EmAnalise],
            [Estado::AguardandoInformacao, Estado::EmAnalise],
            [Estado::Concluida, Estado::EmVerificacao],
            [Estado::Cancelada, Estado::Recebida],
            [Estado::EmAnalise, Estado::EmAnalise],
        ] as [$origem, $destino]) {
            $this->recusada(fn () => $origem->transicionar($destino, true, true, 'atendido'));
        }
    }

    public function test_verifica_identidade_e_autoridade_inclusive_antes_da_conclusao(): void
    {
        foreach ([[false, false], [true, false], [false, true]] as [$identidade, $autoridade]) {
            $this->recusada(fn () => Estado::EmVerificacao->transicionar(Estado::EmAnalise, $identidade, $autoridade));
            $this->recusada(fn () => Estado::EmAnalise->transicionar(Estado::Concluida, $identidade, $autoridade, 'atendido'));
        }
    }

    public function test_resultado_terminal_e_tipado_sem_aceitar_texto_pessoal(): void
    {
        foreach ([null, '', 'dados pessoais livres', 'cancelado_pelo_solicitante'] as $resultado) {
            $this->recusada(fn () => Estado::EmAnalise->transicionar(Estado::Concluida, true, true, $resultado));
        }
        foreach (['atendido', 'parcialmente_atendido', 'indeferido'] as $resultado) {
            $this->assertTrue(Estado::EmAnalise->transicionar(Estado::Concluida, true, true, $resultado)->terminal());
        }
        $this->recusada(fn () => Estado::Recebida->transicionar(Estado::EmVerificacao, resultado: 'atendido'));
    }

    public function test_cancelamento_nao_exige_confirmar_identidade_impossivel_mas_exige_motivo(): void
    {
        $this->recusada(fn () => Estado::EmVerificacao->transicionar(Estado::Cancelada));
        $this->assertSame(Estado::Cancelada, Estado::EmVerificacao->transicionar(Estado::Cancelada, resultado: 'inviavel_verificar'));
        $this->recusada(fn () => Estado::Recebida->transicionar(Estado::Cancelada, resultado: 'atendido'));
    }

    private function recusada(callable $acao): void
    {
        try {
            $acao();
            $this->fail('Operação deveria ser recusada.');
        } catch (DomainException $erro) {
            $this->assertNotEmpty($erro->getMessage());
            $this->assertStringNotContainsString('dados pessoais livres', $erro->getMessage());
        }
    }
}
