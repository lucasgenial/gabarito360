<?php

namespace App\Support;

use RuntimeException;

/** Cifra autenticada do Laravel; índice HMAC separado do valor exibido. */
final class DadoProtegido
{
    /** Aceita os links antigos até expirarem; novos links levam só o índice opaco. */
    public static function conta(string $identificador): ?string
    {
        return preg_match('/^[a-f0-9]{64}$/D', $identificador)
            ? $identificador : self::hash($identificador, 'email');
    }

    public static function hash(?string $valor, string $campo): ?string
    {
        if ($valor === null || $valor === '') return null;
        $chave = config('dados.hash_key');
        if (!is_string($chave) || strlen($chave) < 32) {
            throw new RuntimeException('Chave de busca de dados protegidos não configurada.');
        }
        $normalizado = $campo === 'email' ? mb_strtolower(trim($valor)) : trim($valor);
        return hash_hmac('sha256', $campo . "\0" . $normalizado, $chave);
    }
}
