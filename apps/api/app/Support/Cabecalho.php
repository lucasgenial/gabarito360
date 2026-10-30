<?php

namespace App\Support;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

/**
 * Arquivo: apps/api/app/Support/Cabecalho.php
 * Função: Dados do cabeçalho padrão dos relatórios impressos (escola, rede, INEP,
 *         cidade/UF e se há logo), usados pelo WEB e pelos PDFs gerados em Python.
 */
class Cabecalho
{
    public static function escola(?int $escolaId): array
    {
        $e = $escolaId ? DB::table('escolas')
            ->leftJoin('nucleos', 'nucleos.id', '=', 'escolas.nucleo_id')
            ->leftJoin('redes', 'redes.id', '=', 'nucleos.rede_id')
            ->where('escolas.id', $escolaId)
            ->first(['escolas.id', 'escolas.nome', 'escolas.inep', 'escolas.cidade', 'escolas.uf', 'escolas.logo_path',
                     'nucleos.nome as nucleo', 'redes.nome as rede']) : null;

        if (!$e) {
            return ['escola_id' => null, 'nome' => null, 'rede' => null, 'nucleo' => null, 'detalhe' => null, 'tem_logo' => false];
        }

        return [
            'escola_id' => (int) $e->id,
            'nome'      => $e->nome,
            'rede'      => $e->rede,
            'nucleo'    => $e->nucleo,
            'detalhe'   => collect([
                $e->inep && $e->inep !== 'PENDENTE' ? 'INEP ' . $e->inep : null,
                trim(($e->cidade ?? '') . ($e->uf ? ' – ' . $e->uf : ''), ' –') ?: null,
            ])->filter()->implode(' · ') ?: null,
            'tem_logo'  => self::logoPath($e->logo_path) !== null,
        ];
    }

    /** Cabeçalho de núcleo ou rede (sem escola específica). */
    public static function unidade(string $nome, ?string $rede = null): array
    {
        return ['escola_id' => null, 'nome' => $nome, 'rede' => $rede, 'nucleo' => null, 'detalhe' => null, 'tem_logo' => false];
    }

    /** Caminho absoluto do logo, se o arquivo existir. */
    public static function logoPath(?string $relativo): ?string
    {
        if (!$relativo) {
            return null;
        }
        $p = Storage::disk('public')->path($relativo);

        return is_file($p) ? $p : null;
    }
}
