<?php

namespace App\Support;

use DateTimeImmutable;
use DomainException;

/** Ciclo do artefato. Não autoriza acesso, gera arquivos ou elimina dados. */
enum EstadoExportacaoTitular: string
{
    case Solicitada = 'solicitada';
    case Gerando = 'gerando';
    case Disponivel = 'disponivel';
    case Falhou = 'falhou';
    case Revogada = 'revogada';
    case Expirada = 'expirada';

    public function terminal(): bool
    {
        return in_array($this, [self::Falhou, self::Revogada, self::Expirada], true);
    }

    /** O serviço deve revalidar concessão, sujeito, escopo e versão antes de transicionar. */
    public function transicionar(self $destino): self
    {
        $permitidos = match ($this) {
            self::Solicitada => [self::Gerando, self::Revogada, self::Expirada],
            self::Gerando => [self::Disponivel, self::Falhou, self::Revogada, self::Expirada],
            self::Disponivel => [self::Revogada, self::Expirada],
            self::Falhou, self::Revogada, self::Expirada => [],
        };
        if (!in_array($destino, $permitidos, true)) {
            throw new DomainException('Transição de exportação indisponível.');
        }
        return $destino;
    }

    /** Condição necessária, nunca suficiente para entrega: não substitui autorização. */
    public function disponivelNoPrazo(DateTimeImmutable $agora, ?DateTimeImmutable $expiraEm): bool
    {
        return $this === self::Disponivel && $expiraEm !== null && $agora < $expiraEm;
    }
}
