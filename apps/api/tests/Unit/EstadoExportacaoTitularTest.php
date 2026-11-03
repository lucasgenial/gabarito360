<?php

namespace Tests\Unit;

use App\Support\EstadoExportacaoTitular as Estado;
use DateTimeImmutable;
use DomainException;
use PHPUnit\Framework\TestCase;

class EstadoExportacaoTitularTest extends TestCase
{
    public function test_matriz_completa_recusa_atalhos_e_reabertura(): void
    {
        $arestas = [
            'solicitada:gerando', 'solicitada:revogada', 'solicitada:expirada',
            'gerando:disponivel', 'gerando:falhou', 'gerando:revogada', 'gerando:expirada',
            'disponivel:revogada', 'disponivel:expirada',
        ];
        foreach (Estado::cases() as $origem) {
            foreach (Estado::cases() as $destino) {
                if (in_array($origem->value.':'.$destino->value, $arestas, true)) {
                    $this->assertSame($destino, $origem->transicionar($destino));
                } else {
                    try {
                        $origem->transicionar($destino);
                        $this->fail('Transição indevida: '.$origem->value.':'.$destino->value);
                    } catch (DomainException $e) {
                        $this->assertSame('Transição de exportação indisponível.', $e->getMessage());
                    }
                }
            }
        }
    }

    public function test_fluxo_disponivel_nao_e_terminal_mas_revogacao_e(): void
    {
        $estado = Estado::Solicitada->transicionar(Estado::Gerando)->transicionar(Estado::Disponivel);
        $this->assertFalse($estado->terminal());
        $this->assertTrue($estado->transicionar(Estado::Revogada)->terminal());
        $this->assertTrue($estado->transicionar(Estado::Expirada)->terminal());
        $this->assertTrue(Estado::Gerando->transicionar(Estado::Falhou)->terminal());
        $this->assertFalse(Estado::Solicitada->terminal());
        $this->assertFalse(Estado::Gerando->terminal());
    }

    public function test_prazo_obrigatorio_e_fronteira_exclusiva(): void
    {
        $limite = new DateTimeImmutable('2026-11-02T15:00:00.000000Z');
        $this->assertFalse(Estado::Disponivel->disponivelNoPrazo($limite, null));
        $this->assertTrue(Estado::Disponivel->disponivelNoPrazo($limite->modify('-1 microsecond'), $limite));
        $this->assertFalse(Estado::Disponivel->disponivelNoPrazo($limite, $limite));
        $this->assertFalse(Estado::Disponivel->disponivelNoPrazo($limite->modify('+1 microsecond'), $limite));
    }

    public function test_equivalencia_de_fusos_nao_estende_validade(): void
    {
        $limite = new DateTimeImmutable('2026-11-02T15:00:00Z');
        $this->assertFalse(Estado::Disponivel->disponivelNoPrazo(new DateTimeImmutable('2026-11-02T12:00:00-03:00'), $limite));
        $this->assertTrue(Estado::Disponivel->disponivelNoPrazo(new DateTimeImmutable('2026-11-02T11:59:59-03:00'), $limite));
    }

    public function test_prazo_futuro_nao_libera_artefato_em_outro_estado(): void
    {
        $agora = new DateTimeImmutable('2026-11-02T15:00:00Z');
        foreach ([Estado::Solicitada, Estado::Gerando, Estado::Falhou, Estado::Revogada, Estado::Expirada] as $estado) {
            $this->assertFalse($estado->disponivelNoPrazo($agora, $agora->modify('+1 day')));
        }
    }
}
