<?php

namespace App\Support;

use Illuminate\Support\Facades\Crypt;
use InvalidArgumentException;

/** Primitiva interna. Dígitos válidos não comprovam a identidade do titular. */
final class DocumentoIdentidade
{
    public const VERSAO_INDICE = 1;

    public static function normalizarCpf(string $valor): string
    {
        $valor = trim($valor);
        if (!preg_match('/^(?:[0-9]{11}|[0-9]{3}\.[0-9]{3}\.[0-9]{3}-[0-9]{2})$/D', $valor)) {
            throw new InvalidArgumentException('Documento inválido.');
        }
        $cpf = str_replace(['.', '-'], '', $valor);
        if (preg_match('/^([0-9])\1{10}$/D', $cpf)) throw new InvalidArgumentException('Documento inválido.');
        for ($t = 9; $t < 11; $t++) {
            $soma = 0;
            for ($i = 0; $i < $t; $i++) $soma += (int) $cpf[$i] * ($t + 1 - $i);
            $digito = ($soma * 10) % 11;
            if ($digito === 10) $digito = 0;
            if ($digito !== (int) $cpf[$t]) throw new InvalidArgumentException('Documento inválido.');
        }
        return $cpf;
    }

    public static function indiceCpf(string $valor): string
    {
        return DadoProtegido::hash(self::normalizarCpf($valor), 'identidade:CPF:BR:v1');
    }

    public static function cifrarCpf(string $valor): string
    {
        return 'id1:v1:'.Crypt::encryptString(self::normalizarCpf($valor));
    }

    public static function decifrarCpf(string $valor): string
    {
        if (!str_starts_with($valor, 'id1:v1:')) throw new InvalidArgumentException('Documento protegido incompatível.');
        return self::normalizarCpf(Crypt::decryptString(substr($valor, 7)));
    }
}
