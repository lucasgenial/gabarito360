<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Responses\ApiResponse;
use App\Models\Aluno;
use App\Models\Cartao;
use App\Models\Escola;
use App\Models\Nota;
use App\Models\Nucleo;
use App\Models\Prova;
use App\Models\SincronizacaoSeges;
use App\Models\Usuario;
use App\Models\Visita;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function dirNucleo(Request $request): JsonResponse
    {
        $usuario = $request->user();

        // Descobrir o núcleo do diretor via escopos
        $nucleoId = DB::table('usuario_escopos')
            ->where('usuario_id', $usuario->id)
            ->where('escopo_tipo', 'nucleo')
            ->value('escopo_id');

        if (!$nucleoId) {
            return ApiResponse::forbidden('Usuário não vinculado a nenhum núcleo.');
        }

        $nucleo = Nucleo::findOrFail($nucleoId);

        // IDs das escolas do núcleo
        $escolaIds = Escola::where('nucleo_id', $nucleoId)->pluck('id');

        // KPIs
        $totalEscolas = $escolaIds->count();
        $totalAlunos  = Aluno::whereHas('turma', fn ($q) => $q->whereIn('escola_id', $escolaIds))
            ->where('ativo', true)
            ->count();

        $provasRealizadas = Prova::whereIn('escola_id', $escolaIds)
            ->whereIn('status', ['corrigida', 'em_correcao', 'publicada'])
            ->count();

        $mediaNucleo = Nota::whereHas('prova', fn ($q) => $q->whereIn('escola_id', $escolaIds))
            ->avg('nota_final');

        // Comparativo de escolas
        $escolas = DB::table('escolas')
            ->leftJoin('turmas', function ($j) {
                $j->on('turmas.escola_id', '=', 'escolas.id')->where('turmas.ativo', true);
            })
            ->leftJoin('alunos', function ($j) {
                $j->on('alunos.turma_id', '=', 'turmas.id')->where('alunos.ativo', true);
            })
            ->leftJoin('provas', 'provas.escola_id', '=', 'escolas.id')
            ->leftJoin('notas', 'notas.prova_id', '=', 'provas.id')
            ->whereIn('escolas.id', $escolaIds)
            ->select(
                'escolas.id',
                'escolas.nome',
                DB::raw('COUNT(DISTINCT turmas.id) as total_turmas'),
                DB::raw('COUNT(DISTINCT alunos.id) as total_alunos'),
                DB::raw('ROUND(AVG(notas.nota_final), 1) as media')
            )
            ->groupBy('escolas.id', 'escolas.nome')
            ->orderByDesc('media')
            ->get()
            ->map(function ($e) {
                $media = $e->media ?? 0;
                $e->status = match (true) {
                    $media >= 8.0 => 'destaque',
                    $media >= 7.0 => 'acima_meta',
                    $media >= 6.5 => 'monitoramento',
                    $media >= 5.0 => 'atencao',
                    default        => 'abaixo_meta',
                };
                return $e;
            });

        // Gráfico bimestral (média por bimestre do ano corrente)
        $ano = now()->year;
        $bimestres = [];
        for ($bim = 1; $bim <= 4; $bim++) {
            $mesInicio = ($bim - 1) * 3 + 1;
            $mesFim    = $bim * 3;
            $media = DB::table('notas')
                ->join('provas', 'notas.prova_id', '=', 'provas.id')
                ->whereIn('provas.escola_id', $escolaIds)
                ->whereYear('provas.data_aplicacao', $ano)
                ->whereMonth('provas.data_aplicacao', '>=', $mesInicio)
                ->whereMonth('provas.data_aplicacao', '<=', $mesFim)
                ->avg('notas.nota_final');

            $bimestres[] = [
                'bimestre' => $bim,
                'label'    => "{$bim}º bim.",
                'media'    => $media ? round($media, 1) : null,
            ];
        }

        // Próximas visitas
        $visitas = Visita::where('nucleo_id', $nucleoId)
            ->where('data_visita', '>=', now()->toDateString())
            ->orderBy('data_visita')
            ->limit(3)
            ->with('escola:id,nome')
            ->get(['id', 'escola_id', 'data_visita', 'tipo', 'urgencia']);

        return ApiResponse::success([
            'nucleo' => ['id' => $nucleo->id, 'nome' => $nucleo->nome],
            'kpis' => [
                'total_escolas'    => $totalEscolas,
                'total_alunos'     => $totalAlunos,
                'provas_realizadas' => $provasRealizadas,
                'media_nucleo'     => $mediaNucleo ? round($mediaNucleo, 1) : null,
                'meta_rede'        => 7.0,
            ],
            'escolas'   => $escolas,
            'bimestres' => $bimestres,
            'visitas'   => $visitas,
        ]);
    }

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
