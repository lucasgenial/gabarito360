<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Support\Escopo;
use App\Support\Espacos;
use App\Support\ConsultasPedagogicas;
use App\Http\Responses\ApiResponse;
use App\Models\Turma;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class TurmaController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $consulta = new ConsultasPedagogicas($request->user());
        $ids   = Escopo::turmaIds($request->user());
        $query = Escopo::turmas($request->user());

        if ($busca = $request->query('busca')) {
            $query->where(function ($q) use ($busca) {
                $q->where('nome', 'like', "%{$busca}%")
                  ->orWhere('serie', 'like', "%{$busca}%");
            });
        }

        if ($escolaId = $request->query('escola_id')) {
            $query->where('escola_id', $escolaId);
        }

        if ($anoLetivo = $request->query('ano_letivo')) {
            $query->where('ano_letivo', $anoLetivo);
        }

        if ($request->has('ativo')) {
            $query->where('ativo', (bool) $request->query('ativo'));
        }

        $turmas = $query->with('escola:id,nome,nucleo_id')
            ->orderBy('nome')
            ->get()
            ->map(function (Turma $t) use ($consulta) {
                $totalAlunos = $consulta->tabela('alunos')
                    ->where('turma_id', $t->id)->when(Espacos::ativo(), fn ($q) => $q->where('espaco_id', $t->espaco_id))
                    ->where('ativo', true)
                    ->count();

                $media = $consulta->tabela('notas')->where('turma_id', $t->id)->when(Espacos::ativo(), fn ($q) => $q->where('espaco_id', $t->espaco_id))->avg('nota_final');

                return array_merge($t->toArray(), [
                    'total_alunos' => $totalAlunos,
                    'escola_nome'  => $t->escola?->nome,
                    'media_turma'  => $media !== null ? round((float) $media, 1) : null,
                ]);
            });

        $baseQ = Escopo::turmas($request->user());

        $kpis = [
            'total_turmas'  => $baseQ->count(),
            'total_ativas'  => (clone $baseQ)->where('ativo', true)->count(),
            'total_alunos'  => (int) $consulta->tabela('alunos')
                ->joinSub($consulta->tabela('turmas'), 'turmas', 'alunos.turma_id', '=', 'turmas.id')
                ->when(Espacos::ativo(), fn ($q) => $q->whereColumn('alunos.espaco_id', 'turmas.espaco_id'))
                ->when($ids !== null, fn ($q) => $q->whereIn('turmas.id', $ids))
                ->where('alunos.ativo', true)
                ->count(),
        ];

        return ApiResponse::success(['turmas' => $turmas, 'kpis' => $kpis]);
    }

    public function store(Request $request): JsonResponse
    {
        $request->validate(['escola_id' => 'required|integer']);
        $escolaId = (int) $request->input('escola_id');
        abort_unless(\App\Support\Cadastro::escolaNoEscopo($request->user(), $escolaId)
            && DB::table('escolas')->where('id', $escolaId)->exists(), 404);
        $data = $request->validate([
            'escola_id'  => 'required|integer|exists:escolas,id',
            'nome'       => 'required|string|max:50',
            'serie'      => 'required|string|max:50',
            'turno'      => ['required', Rule::in(['manha','tarde','noite','integral'])],
            'ano_letivo' => 'required|integer|min:2020|max:2099',
            'ativo'      => 'sometimes|boolean',
        ]);

        $turma = new Turma($data);
        if (Espacos::ativo()) $turma->forceFill(Espacos::autoria(Espacos::daEscola($request->user(), (int) $data['escola_id']), $request->user()));
        $turma->save();

        return ApiResponse::success($turma, null, 201);
    }

    public function show(Request $request, int $id): JsonResponse
    {
        $consulta = new ConsultasPedagogicas($request->user());
        $query = Escopo::turmas($request->user())->with('escola:id,nome,nucleo_id');

        $turma = $query->find($id);

        if (!$turma) {
            return ApiResponse::notFound('Turma não encontrada.');
        }

        $alunos = $consulta->tabela('alunos')
            ->where('turma_id', $id)->when(Espacos::ativo(), fn ($q) => $q->where('espaco_id', $turma->espaco_id))
            ->orderBy('nome')
            ->select('id', 'nome', 'avatar_id', 'matricula', 'data_nascimento', 'ativo')
            ->get()
            ->map(function ($a) use ($turma, $consulta) {
                $a->media_geral = $consulta->tabela('notas')->where('aluno_id', $a->id)->when(Espacos::ativo(), fn ($q) => $q->where('espaco_id', $turma->espaco_id))->avg('nota_final');
                $a->media_geral = $a->media_geral !== null ? round((float) $a->media_geral, 1) : null;
                $a->total_provas = $consulta->tabela('notas')->where('aluno_id', $a->id)->when(Espacos::ativo(), fn ($q) => $q->where('espaco_id', $turma->espaco_id))->count();
                return $a;
            });

        $totalAlunos = $alunos->where('ativo', true)->count();

        $mediaTurma = $consulta->tabela('notas')->where('turma_id', $id)->when(Espacos::ativo(), fn ($q) => $q->where('espaco_id', $turma->espaco_id))->avg('nota_final');

        $provas = $consulta->tabela('prova_turmas')
            ->joinSub($consulta->tabela('provas'), 'provas', 'prova_turmas.prova_id', '=', 'provas.id')
            ->where('prova_turmas.turma_id', $id)
            ->when(Espacos::ativo(), fn ($q) => $q->where('prova_turmas.espaco_id', $turma->espaco_id)->whereColumn('provas.espaco_id', 'prova_turmas.espaco_id'))
            ->orderByDesc('provas.data_aplicacao')
            ->select('provas.id', 'provas.titulo', 'provas.disciplina', 'provas.status', 'provas.data_aplicacao')
            ->get()
            ->map(function ($p) use ($id, $turma, $consulta) {
                $media = $consulta->tabela('notas')
                    ->where('prova_id', $p->id)
                    ->where('turma_id', $id)->when(Espacos::ativo(), fn ($q) => $q->where('espaco_id', $turma->espaco_id))
                    ->avg('nota_final');

                return [
                    'id'             => $p->id,
                    'titulo'         => $p->titulo,
                    'disciplina'     => $p->disciplina,
                    'data_aplicacao' => $p->data_aplicacao,
                    'media'          => $media !== null ? round((float) $media, 1) : null,
                    'status'         => $p->status,
                ];
            });

        return ApiResponse::success(array_merge($turma->toArray(), [
            'total_alunos'  => $totalAlunos,
            'escola_nome'   => $turma->escola?->nome,
            'media_turma'   => $mediaTurma !== null ? round((float) $mediaTurma, 1) : null,
            'total_provas'  => $provas->count(),
            'alunos'        => $alunos,
            'provas'        => $provas,
        ]));
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $turma = Escopo::turmas($request->user())->find($id);

        if (!$turma) {
            return ApiResponse::notFound('Turma não encontrada.');
        }

        $data = $request->validate([
            'escola_id'  => 'sometimes|integer|exists:escolas,id',
            'nome'       => 'sometimes|string|max:50',
            'serie'      => 'sometimes|string|max:50',
            'turno'      => ['sometimes', Rule::in(['manha','tarde','noite','integral'])],
            'ano_letivo' => 'sometimes|integer|min:2020|max:2099',
            'ativo'      => 'sometimes|boolean',
        ]);

        if (isset($data['escola_id'])) {
            abort_unless((int) $data['escola_id'] === (int) $turma->escola_id, 422, 'Transferência entre escolas exige procedimento próprio.');
        }
        $turma->update($data);

        return ApiResponse::success($turma->fresh());
    }

    public function toggle(Request $request, int $id): JsonResponse
    {
        $turma = Escopo::turmas($request->user())->find($id);

        if (!$turma) {
            return ApiResponse::notFound('Turma não encontrada.');
        }

        $turma->update(['ativo' => !$turma->ativo]);

        return ApiResponse::success(
            ['ativo' => $turma->ativo],
            $turma->ativo ? 'Turma ativada.' : 'Turma desativada.'
        );
    }
}
