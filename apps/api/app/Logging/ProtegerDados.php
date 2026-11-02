<?php

namespace App\Logging;

use Illuminate\Log\Logger;
use Monolog\LogRecord;
use Throwable;

/** Lista positiva: SQL, corpos HTTP/SMTP, objetos e argumentos nunca chegam ao arquivo. */
final class ProtegerDados
{
    private static ?\WeakMap $handlers = null;

    private const MENSAGENS = [
        'auditoria_enfileiramento_indisponivel',
        'MP Webhook: assinatura inválida', 'MP Webhook recebido',
        'OMR nativo: leitura falhou', 'Imagens do cartão não geradas',
        'Assinatura: trial expirado, acesso suspenso.', 'Assinatura: inadimplente após carência, acesso suspenso.',
    ];

    public function __invoke(Logger $logger): void
    {
        // No handler: roda depois da interpolação dos placeholders e do contexto global.
        self::$handlers ??= new \WeakMap;
        foreach ($logger->getLogger()->getHandlers() as $handler) {
            if (method_exists($handler, 'pushProcessor') && !isset(self::$handlers[$handler])) {
                self::$handlers[$handler] = true;
                $handler->pushProcessor(fn (LogRecord $record) => self::registro($record));
            }
        }
    }

    public static function registro(LogRecord $record): LogRecord
    {
        $contexto = [];
        foreach (['cartao', 'prova_id', 'tentativa', 'exit_code', 'assinatura_id'] as $campo) {
            if (isset($record->context[$campo]) && is_int($record->context[$campo])) {
                $contexto[$campo] = $record->context[$campo];
            }
        }
        $erro = $record->context['exception'] ?? null;
        if ($erro instanceof Throwable) {
            $contexto['exception'] = self::excecao($erro);
        }
        return $record->with(
            message: in_array($record->message, self::MENSAGENS, true) ? $record->message : 'Evento registrado; conteúdo livre omitido.',
            context: $contexto,
            extra: [],
        );
    }

    public static function excecao(Throwable $erro): array
    {
        // Sem mensagem, bindings SQL, cadeia anterior ou argumentos da chamada.
        return [
            'classe' => get_class($erro),
            'arquivo' => basename($erro->getFile()),
            'linha' => $erro->getLine(),
            'pilha' => array_map(fn ($f) => [
                'arquivo' => basename($f['file'] ?? ''),
                'linha' => $f['line'] ?? null,
                'classe' => $f['class'] ?? null,
                'funcao' => $f['function'],
            ], array_slice($erro->getTrace(), 0, 20)),
        ];
    }
}
