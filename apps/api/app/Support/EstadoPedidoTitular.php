<?php

namespace App\Support;

use DomainException;

/** Contrato de estado; não autoriza acesso nem executa ações sobre dados. */
enum EstadoPedidoTitular: string
{
    case Recebida = 'recebida';
    case EmVerificacao = 'em_verificacao';
    case EmAnalise = 'em_analise';
    case AguardandoInformacao = 'aguardando_informacao';
    case Concluida = 'concluida';
    case Cancelada = 'cancelada';

    public function terminal(): bool
    {
        return in_array($this, [self::Concluida, self::Cancelada], true);
    }

    /**
     * Identidade/representação e autoridade institucional são fatos do servidor,
     * nunca booleanos aceitos do formulário. Responsável/escopo são revalidados
     * pelo serviço transacional antes de usar este contrato.
     */
    public function transicionar(
        self $destino,
        bool $identidadeVerificada = false,
        bool $autoridadeVerificada = false,
        ?string $resultado = null,
    ): self {
        $permitidos = match ($this) {
            self::Recebida => [self::EmVerificacao, self::Cancelada],
            self::EmVerificacao => [self::EmAnalise, self::AguardandoInformacao, self::Cancelada],
            self::EmAnalise => [self::AguardandoInformacao, self::Concluida, self::Cancelada],
            self::AguardandoInformacao => [self::EmVerificacao, self::Cancelada],
            self::Concluida, self::Cancelada => [],
        };
        if (!in_array($destino, $permitidos, true)) {
            throw new DomainException('Transição de protocolo indisponível.');
        }
        if (in_array($destino, [self::EmAnalise, self::Concluida], true)
            && (!$identidadeVerificada || !$autoridadeVerificada)) {
            throw new DomainException('Verificação do protocolo pendente.');
        }
        $resultados = match ($destino) {
            self::Concluida => ['atendido', 'parcialmente_atendido', 'indeferido'],
            self::Cancelada => ['cancelado_pelo_solicitante', 'duplicado', 'inviavel_verificar'],
            default => [],
        };
        if (($destino->terminal() && !in_array($resultado, $resultados, true))
            || (!$destino->terminal() && $resultado !== null)) {
            throw new DomainException('Resultado incompatível com o protocolo.');
        }
        return $destino;
    }
}
