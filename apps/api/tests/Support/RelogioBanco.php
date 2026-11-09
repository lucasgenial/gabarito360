<?php

namespace Tests\Support;

use Illuminate\Database\Connection;
use Illuminate\Database\Events\ConnectionEstablished;
use Illuminate\Support\Carbon;

/** Harness exclusivo de teste: mantém o relógio SQL e travelTo na conexão descartável. */
final class RelogioBanco
{
    public static function instalar($app): \Closure
    {
        if (!$app->environment('testing')) throw new \LogicException('Relógio de ensaio exige ambiente testing.');
        $instaladas = new \SplObjectStorage();
        $instalar = function (Connection $conexao) use ($instaladas) {
            if ($instaladas->contains($conexao)) return;
            if (in_array($conexao->getDriverName(), ['mysql', 'mariadb'], true)
                && !preg_match('/^g360_[a-z0-9_]*(?:test|ci|ensaio)(?:_[a-z0-9_]*)?$/D', $conexao->getDatabaseName())) {
                throw new \LogicException('Relógio de ensaio exige banco descartável identificado para testes.');
            }
            $instaladas->attach($conexao);
            $pdoAnterior = null;
            $instanteAnterior = null;
            $conexao->beforeExecuting(function ($sql, $bindings, Connection $c) use (&$pdoAnterior, &$instanteAnterior) {
                // run() invoca os callbacks antes da reconexão automática; migrate:fresh pode desconectar o PDO.
                $c->reconnectIfMissingConnection();
                $pdo = $c->getPdo();
                if ($c->getDriverName() === 'sqlite') {
                    if ($pdoAnterior !== $pdo) $pdo->sqliteCreateFunction('current_timestamp', fn () => now('UTC')->format('Y-m-d H:i:s'), 0);
                    $pdoAnterior = $pdo;
                    return;
                }
                if (!in_array($c->getDriverName(), ['mysql', 'mariadb'], true)) return;
                $instante = Carbon::hasTestNow() ? (int) now('UTC')->timestamp : 0;
                if ($pdoAnterior !== $pdo || $instanteAnterior !== $instante) {
                    // PDO direto evita recursão dos callbacks. Nunca executado por código de runtime.
                    $pdo->exec('SET SESSION timestamp = '.$instante);
                    $pdoAnterior = $pdo;
                    $instanteAnterior = $instante;
                }
            });
        };
        foreach ($app['db']->getConnections() as $conexao) $instalar($conexao);
        $app['events']->listen(ConnectionEstablished::class, fn ($evento) => $instalar($evento->connection));
        // Alguns testes substituem o DatabaseManager por um mock. Restaura somente conexões reais acompanhadas.
        return static fn () => self::restaurarConexoes($instaladas);
    }

    public static function restaurar($app): void
    {
        self::restaurarConexoes($app['db']->getConnections());
    }

    private static function restaurarConexoes(iterable $conexoes): void
    {
        foreach ($conexoes as $conexao) {
            if (in_array($conexao->getDriverName(), ['mysql', 'mariadb'], true) && $conexao->getRawPdo() instanceof \PDO) {
                $conexao->getRawPdo()->exec('SET SESSION timestamp = 0');
            }
        }
    }
}
