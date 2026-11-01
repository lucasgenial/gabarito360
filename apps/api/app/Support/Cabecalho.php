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
    public static function contexto(\App\Models\Usuario $ator, ?int $escola): array
    {
        return EspacoPessoal::doAtor($ator)
            ? self::unidade(ContextoVinculo::resolver($ator)->espaco->nome)
            : self::escola($escola);
    }
    public static function escola(?int $escolaId): array
    {
        $e = $escolaId ? DB::table('escolas')
            ->leftJoin('nucleos', 'nucleos.id', '=', 'escolas.nucleo_id')
            ->leftJoin('redes', 'redes.id', '=', 'nucleos.rede_id')
            ->where('escolas.id', $escolaId)
            ->first(['escolas.id', 'escolas.nome', 'escolas.inep', 'escolas.cidade', 'escolas.uf', 'escolas.logo_path',
                     'nucleos.nome as nucleo', 'redes.id as rede_id', 'redes.nome as rede', 'redes.logo_path as rede_logo']) : null;

        if (!$e) {
            return ['escola_id' => null, 'nome' => null, 'rede_id' => null, 'rede' => null, 'nucleo' => null, 'detalhe' => null, 'tem_logo' => false, 'tem_logo_rede' => false];
        }

        return [
            'escola_id' => (int) $e->id,
            'nome'      => $e->nome,
            'rede_id'   => $e->rede_id ? (int) $e->rede_id : null,
            'rede'      => $e->rede,
            'nucleo'    => $e->nucleo,
            'detalhe'   => collect([
                $e->inep && $e->inep !== 'PENDENTE' ? 'INEP ' . $e->inep : null,
                trim(($e->cidade ?? '') . ($e->uf ? ' – ' . $e->uf : ''), ' –') ?: null,
            ])->filter()->implode(' · ') ?: null,
            'tem_logo'  => self::logoPath($e->logo_path) !== null,
            'tem_logo_rede' => self::logoPath($e->rede_logo) !== null,
        ];
    }

    /** Cabeçalho de núcleo ou rede (sem escola específica): mostra o logo do ente, se houver. */
    public static function unidade(string $nome, ?string $rede = null, ?int $redeId = null): array
    {
        $logoRede = $redeId ? DB::table('redes')->where('id', $redeId)->value('logo_path') : null;

        return ['escola_id' => null, 'nome' => $nome, 'rede_id' => $redeId, 'rede' => $rede, 'nucleo' => null, 'detalhe' => null,
                'tem_logo' => false, 'tem_logo_rede' => self::logoPath($logoRede) !== null];
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
