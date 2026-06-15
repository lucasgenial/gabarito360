<?php

namespace App\Actions\Relatorios;

use App\Models\Comparativo;
use App\Models\Nucleo;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Monta e persiste o comparativo das escolas de um núcleo (média, aprovação e
 * volume de resultados vigentes), alimentando o painel de diretor de núcleo.
 */
class BuildComparativoAction
{
    /**
     * @param  Collection<int, string>  $escolaIds  escolas visíveis ao ator no núcleo
     */
    public function execute(Nucleo $nucleo, Collection $escolaIds, ?string $provaId, User $actor): Comparativo
    {
        $meta = (float) config('gabarito360.resultados.meta_aprovacao', 60.0);

        $rows = DB::table('escolas as e')
            ->leftJoin('aplicacoes as a', function ($join) use ($provaId): void {
                $join->on('a.escola_id', '=', 'e.id');
                if (is_string($provaId) && $provaId !== '') {
                    $join->where('a.prova_id', '=', $provaId);
                }
            })
            ->leftJoin('resultados as r', function ($join): void {
                $join->on('r.aplicacao_id', '=', 'a.id')->where('r.status', '=', 'vigente');
            })
            ->whereIn('e.id', $escolaIds)
            ->groupBy('e.id', 'e.nome')
            ->selectRaw('
                e.id as escola_id,
                e.nome as escola_nome,
                COUNT(r.id) as total,
                AVG(r.nota_percentual) as media_nota,
                MIN(r.nota_percentual) as menor_nota,
                MAX(r.nota_percentual) as maior_nota,
                SUM(CASE WHEN r.nota_percentual >= ? THEN 1 ELSE 0 END) as aprovados
            ', [$meta])
            ->orderByDesc('media_nota')
            ->get();

        $resultado = $rows->map(function ($row): array {
            $total = (int) $row->total;

            return [
                'escola_id' => $row->escola_id,
                'escola_nome' => $row->escola_nome,
                'total' => $total,
                'media_nota' => $row->media_nota !== null ? round((float) $row->media_nota, 2) : null,
                'menor_nota' => $row->menor_nota !== null ? (float) $row->menor_nota : null,
                'maior_nota' => $row->maior_nota !== null ? (float) $row->maior_nota : null,
                'aprovados' => (int) $row->aprovados,
                'aprovacao_percentual' => $total > 0 ? round((int) $row->aprovados / $total * 100, 2) : 0.0,
            ];
        })->values()->all();

        return Comparativo::query()->create([
            'tipo' => 'escolas_nucleo',
            'nucleo_id' => $nucleo->id,
            'prova_id' => is_string($provaId) && $provaId !== '' ? $provaId : null,
            'parametros' => array_filter(['nucleo_id' => $nucleo->id, 'prova_id' => $provaId, 'meta_aprovacao' => $meta]),
            'resultado' => $resultado,
            'gerado_por_id' => $actor->id,
            'gerado_at' => now(),
        ]);
    }
}
