<?php

namespace App\Support\Catalogo;

use RuntimeException;

final class PoliticaEdicaoCatalogo
{
    public static function conferir(int $ano, ?object $vigente, ?string $substituir, ?string $motivo): void
    {
        if ($motivo !== null && mb_strlen($motivo)>500) throw new RuntimeException('Motivo excede 500 caracteres.');
        if ($vigente && $ano < (int)$vigente->ano) throw new RuntimeException('Edição anterior à vigente recusada.');
        if ($substituir !== null && (!$vigente || $substituir !== $vigente->id || trim((string)$motivo) === '')) throw new RuntimeException('Revisão precisa indicar a publicação vigente e motivo.');
        if ($vigente && $ano === (int)$vigente->ano && $substituir !== $vigente->id) throw new RuntimeException('Substituição no mesmo ano exige revisão explícita.');
    }
}
