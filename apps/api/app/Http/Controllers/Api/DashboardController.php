<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Responses\ApiResponse;
use App\Models\Aluno;
use App\Models\Cartao;
use App\Models\Escola;
use App\Models\Nota;
use App\Models\Prova;
use App\Models\SincronizacaoSeges;
use App\Models\Usuario;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function admin(): JsonResponse
    {
        $totalEscolas   = Escola::count();
        $totalAtivas    = Escola::where('ativo', true)->count();
        $totalAlunos    = Aluno::where('ativo', true)->count();

        $provasMes = Prova::whereMonth('data_aplicacao', now()->month)
            ->whereYear('data_aplicacao', now()->year)
            ->count();

        $mediaRede = Nota::avg('nota_final');

        $top5 = DB::table('notas')
            ->join('provas', 'notas.prova_id', '=', 'provas.id')
            ->join('escolas', 'provas.escola_id', '=', 'escolas.id')
            ->select('escolas.id', 'escolas.nome', DB::raw('ROUND(AVG(notas.nota_final), 1) as media'))
            ->groupBy('escolas.id', 'escolas.nome')
            ->orderByDesc('media')
            ->limit(5)
            ->get();

        $metaRede = 7.0;
        $escolasAbaixo = DB::table('notas')
            ->join('provas', 'notas.prova_id', '=', 'provas.id')
            ->join('escolas', 'provas.escola_id', '=', 'escolas.id')
            ->select('escolas.id')
            ->groupBy('escolas.id')
            ->havingRaw('AVG(notas.nota_final) < ?', [$metaRede])
            ->get()
            ->count();

        $cartoesAmbiguos = Cartao::where('status', 'ambiguo')->count();

        $ultimaSync = SincronizacaoSeges::latest('iniciado_em')->first();
        $segesAtraso = null;
        if ($ultimaSync && $ultimaSync->duracao_minutos !== null) {
            $segesAtraso = $ultimaSync->duracao_minutos;
        }

        $ultimosAcessos = Usuario::whereNotNull('ultimo_acesso')
            ->where('ativo', true)
            ->orderByDesc('ultimo_acesso')
            ->limit(5)
            ->get(['id', 'nome', 'perfil', 'escola_nome', 'ultimo_acesso']);

        return ApiResponse::success([
            'kpis' => [
                'total_escolas'     => $totalEscolas,
                'total_escolas_ativas' => $totalAtivas,
                'total_alunos'      => $totalAlunos,
                'provas_mes'        => $provasMes,
                'media_rede'        => $mediaRede ? round($mediaRede, 1) : null,
                'meta_rede'         => $metaRede,
            ],
            'top5_escolas'   => $top5,
            'alertas' => [
                'cartoes_ambiguos'   => $cartoesAmbiguos,
                'escolas_abaixo_meta' => $escolasAbaixo,
                'seges_atraso_min'   => $segesAtraso,
            ],
            'ultimos_acessos' => $ultimosAcessos,
        ]);
    }
}
