<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Support\Escopo;
use App\Support\Espacos;
use App\Support\EspacoPessoal;
use App\Support\ConsultasPedagogicas;
use App\Support\{Permissao,PerfisEscolares};
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
        $conteudos = $this->conteudos($request);
        $consulta = new ConsultasPedagogicas($request->user(),historico:true);
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
            $query->where('ativo', $request->boolean('ativo'));
        }

        $lista = $query->with('escola:id,nome,nucleo_id')->orderBy('nome')->get();
        $versoes = app(\App\Services\ArquivamentoCadastro::class)->versoes('turma',$lista->modelKeys());
        $turmas = $lista->map(function (Turma $t) use ($consulta, $request, $versoes, $conteudos) {
                $t->setAttribute('arquivamento',app(\App\Services\ArquivamentoCadastro::class)->estado($t,$versoes[$t->id]??0));
                $totalAlunos = $consulta->tabela('alunos')
                    ->where('turma_id', $t->id)->when(Espacos::ativo(), fn ($q) => $q->where('espaco_id', $t->espaco_id))
                    ->where('ativo', true)
                    ->count();

                if (!$conteudos['agregado']) return array_merge($t->toArray(), [
                    'total_alunos' => $totalAlunos, 'escola_nome' => $t->escola?->nome,
                ]);

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
        $pessoal = EspacoPessoal::doAtor($request->user());
        $request->validate(['escola_id' => $pessoal ? 'prohibited' : 'required|integer']);
        $escolaId = (int) $request->input('escola_id');
        abort_unless($pessoal || (\App\Support\Cadastro::escolaNoEscopo($request->user(), $escolaId)
            && DB::table('escolas')->where('id', $escolaId)->exists()), 404);
        $data = $request->validate([
            'escola_id'  => $pessoal ? 'prohibited' : 'required|integer|exists:escolas,id',
            'nome'       => 'required|string|max:50',
            'serie'      => 'required|string|max:50',
            'turno'      => ['required', Rule::in(['manha','tarde','noite','integral'])],
            'ano_letivo' => 'required|integer|min:2020|max:2099',
            'ativo'      => 'sometimes|boolean',
            'sala' => 'sometimes|nullable|string|max:50',
            'capacidade' => 'sometimes|nullable|integer|min:1|max:1000',
            'etapa_ensino' => ['sometimes', 'nullable', Rule::in(Turma::ETAPAS)],
            'observacoes' => 'sometimes|nullable|string|max:1000',
        ]);

        $turma = app(\App\Services\ArquivamentoCadastro::class)->criar($request->user(),'turma',$data,function($valores)use($pessoal,$request){
            $turma = new Turma($valores);
            if (Espacos::ativo()) $turma->forceFill(Espacos::autoria($pessoal ?? Espacos::daEscola($request->user(), (int)$valores['escola_id']),$request->user()));
            if ($pessoal) $turma->forceFill(['escola_id'=>null,'espaco_tipo'=>'pessoal']);
            $turma->save();return $turma;
        });
        $turma->setAttribute('arquivamento',app(\App\Services\ArquivamentoCadastro::class)->estado($turma));

        return ApiResponse::success($turma, null, 201);
    }

    public function show(Request $request, int $id): JsonResponse
    {
        $conteudos = $this->conteudos($request);
        $consulta = new ConsultasPedagogicas($request->user(),historico:true);
        $query = Escopo::turmas($request->user())->with('escola:id,nome,nucleo_id');

        $turma = $query->find($id);

        if (!$turma) {
            return ApiResponse::notFound('Turma não encontrada.');
        }

        $turma->setAttribute('arquivamento',app(\App\Services\ArquivamentoCadastro::class)->estado($turma));
        $cadastros = $consulta->tabela('alunos')
            ->where('turma_id', $id)->when(Espacos::ativo(), fn ($q) => $q->where('espaco_id', $turma->espaco_id));
        $campos = ['id', 'nome', 'avatar_id', 'imagem_versao', 'foto_path', 'matricula'];
        if (!$conteudos['administrativo']) $campos[] = 'data_nascimento';
        $campos[] = 'ativo';
        $alunos = $conteudos['nominal'] ? (clone $cadastros)->orderBy('nome')->get($campos) : collect();
        $totalAlunos = $conteudos['nominal'] ? $alunos->where('ativo', true)->count() : (clone $cadastros)->where('ativo', true)->count();

        $this->estadosDosAlunos($alunos);
        // Construir o mesmo escopo completo uma vez por requisição. Os clones mantêm
        // todos os filtros de integridade/autorização, inclusive o histórico do aluno.
        $notas = $conteudos['agregado'] ? $consulta->tabela('notas')
            ->when(Espacos::ativo(), fn ($q) => $q->where('espaco_id', $turma->espaco_id)) : null;
        $porAluno = !$conteudos['resultados'] || $alunos->isEmpty() ? collect() : (clone $notas)
            ->whereIn('aluno_id', $alunos->pluck('id'))
            ->select('aluno_id')->selectRaw('AVG(nota_final) as media_geral, COUNT(*) as total_provas')
            ->groupBy('aluno_id')->get()->keyBy('aluno_id');
        $alunos->transform(function ($a) use ($porAluno, $conteudos) {
            $a->tem_foto = !empty($a->foto_path); unset($a->foto_path);
            if ($conteudos['resultados']) {
                $agregado = $porAluno->get($a->id);
                $a->media_geral = $agregado?->media_geral !== null ? round((float) $agregado->media_geral, 1) : null;
                $a->total_provas = (int) ($agregado?->total_provas ?? 0);
            }
            return $a;
        });
        // AVG continua no banco: não recompor a média usando médias arredondadas.
        $mediaTurma = $conteudos['agregado'] ? (clone $notas)->where('turma_id', $id)->avg('nota_final') : null;

        $provas = $conteudos['provas'] ? $consulta->tabela('prova_turmas')
            ->joinSub($consulta->tabela('provas'), 'provas', 'prova_turmas.prova_id', '=', 'provas.id')
            ->where('prova_turmas.turma_id', $id)
            ->when(Espacos::ativo(), fn ($q) => $q->where('prova_turmas.espaco_id', $turma->espaco_id)->whereColumn('provas.espaco_id', 'prova_turmas.espaco_id'))
            ->orderByDesc('provas.data_aplicacao')
            ->select('provas.id', 'provas.titulo', 'provas.disciplina', 'provas.status', 'provas.data_aplicacao')
            ->get() : collect();
        $porProva = !$conteudos['agregado'] || $provas->isEmpty() ? collect() : (clone $notas)
            ->where('turma_id', $id)->whereIn('prova_id', $provas->pluck('id'))
            ->select('prova_id')->selectRaw('AVG(nota_final) as media')
            ->groupBy('prova_id')->get()->keyBy('prova_id');
        $provas = $provas->map(function ($p) use ($porProva, $conteudos) {
            $media = $porProva->get($p->id)?->media;
            $dados = [
                'id'             => $p->id,
                'titulo'         => $p->titulo,
                'disciplina'     => $p->disciplina,
                'data_aplicacao' => $p->data_aplicacao,
            ];
            if ($conteudos['agregado']) $dados['media'] = $media !== null ? round((float) $media, 1) : null;
            $dados['status'] = $p->status;
            return $dados;
        });

        if ($conteudos['administrativo']) return ApiResponse::success(array_merge($turma->toArray(), [
            'escola_nome'=>$turma->escola?->nome,'total_alunos'=>$totalAlunos,'alunos'=>$alunos,
        ]));
        $dados = array_merge($turma->toArray(), [
            'total_alunos'  => $totalAlunos,
            'escola_nome'   => $turma->escola?->nome,
        ]);
        if ($conteudos['agregado']) $dados['media_turma'] = $mediaTurma !== null ? round((float) $mediaTurma, 1) : null;
        if ($conteudos['provas']) $dados['total_provas'] = $provas->count();
        $dados['alunos'] = $alunos;
        $dados['provas'] = $provas;
        return ApiResponse::success($dados);
    }

    /** Permissão de cadastro não concede agregados, resultados nominais ou provas. Sem cache entre requisições. */
    private function conteudos(Request $request): array
    {
        $administrativo = PerfisEscolares::administrativo($request->user()->perfil);
        $permissoes = Permissao::efetivas($request->user());
        $nominal = in_array('aluno.ver',$permissoes,true);
        $agregado = !$administrativo && in_array('relatorio.turma',$permissoes,true);
        return ['administrativo'=>$administrativo,'nominal'=>$nominal,'agregado'=>$agregado,
            'resultados'=>$agregado && $nominal && in_array('resultado.ver',$permissoes,true),
            'provas'=>!$administrativo && in_array('prova.ver',$permissoes,true)];
    }

    private function estadosDosAlunos(\Illuminate\Support\Collection $alunos): void
    {
        $versoes=app(\App\Services\ArquivamentoCadastro::class)->versoes('aluno',$alunos->pluck('id')->all());
        foreach($alunos as $a)$a->arquivamento=['arquivado'=>!(bool)$a->ativo,'versao'=>$versoes[$a->id]??0];
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $turma = Escopo::turmas($request->user())->find($id);

        if (!$turma) {
            return ApiResponse::notFound('Turma não encontrada.');
        }

        $data = $request->validate([
            'escola_id'  => EspacoPessoal::doAtor($request->user()) ? 'prohibited' : 'sometimes|integer|exists:escolas,id',
            'nome'       => 'sometimes|string|max:50',
            'serie'      => 'sometimes|string|max:50',
            'turno'      => ['sometimes', Rule::in(['manha','tarde','noite','integral'])],
            'ano_letivo' => 'sometimes|integer|min:2020|max:2099',
            'ativo'      => 'sometimes|boolean',
            'sala' => 'sometimes|nullable|string|max:50',
            'capacidade' => 'sometimes|nullable|integer|min:1|max:1000',
            'etapa_ensino' => ['sometimes', 'nullable', Rule::in(Turma::ETAPAS)],
            'observacoes' => 'sometimes|nullable|string|max:1000',
        ]);

        if (isset($data['escola_id'])) {
            abort_unless((int) $data['escola_id'] === (int) $turma->escola_id, 422, 'Transferência entre escolas exige procedimento próprio.');
        }
        $turma = app(\App\Services\ArquivamentoCadastro::class)->atualizar($request->user(),'turma',$id,$data);
        return ApiResponse::success(array_merge($turma->toArray(),['arquivamento'=>app(\App\Services\ArquivamentoCadastro::class)->estado($turma)]));
    }

    public function toggle(Request $request, int $id): JsonResponse
    {
        $d=$request->validate(['esperado_ativo'=>'sometimes|boolean','versao'=>'sometimes|integer|min:0','chave'=>'sometimes|uuid']);
        $estado=app(\App\Services\ArquivamentoCadastro::class)->toggleLegado($request->user(),'turma',$id,$d);
        return ApiResponse::success($estado,$estado['ativo']?'Turma ativada.':'Turma desativada.');
    }
}
