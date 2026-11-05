<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Responses\ApiResponse;
use App\Models\Prova;
use App\Support\Escopo;
use App\Support\Espacos;
use App\Support\EspacoPessoal;
use App\Support\ConsultasPedagogicas;
use App\Services\CurriculoProva;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class ProvaController extends Controller
{
    public function opcoesDisciplinas(Request $r): JsonResponse
    {
        abort_unless(CurriculoProva::aplicavel($r->user()),404);
        $d=$r->validate(['turma_ids'=>'sometimes|array|max:500','turma_ids.*'=>'integer|min:1|distinct',
            'rascunho_ids'=>'sometimes|array|max:200','rascunho_ids.*'=>'integer|min:1|distinct','prova_id'=>'nullable|integer|min:1']);
        $p=isset($d['prova_id'])?Escopo::provaEditavel($r->user(),(int)$d['prova_id']):null;
        if (isset($d['prova_id'])) abort_unless($p,404);
        $opcoes=app(\App\Services\OpcoesDisciplinaProva::class)->listar($r->user(),array_map('intval',$d['turma_ids']??[]),$p?'editar':'criar');
        $resumo=$p ? (app(CurriculoProva::class)->resumos($r->user(),[$p->id])[$p->id]??null) : null;
        $ids=array_map('intval',$d['rascunho_ids']??array_column($resumo['disciplinas']??[],'id'));
        $visiveis=app(\App\Services\GestaoDisciplinas::class)->disciplinas($r->user())->whereIn('d.id',$ids)->get(['d.id','d.nome','d.ativo'])->keyBy('id');
        $salvas=collect($resumo['disciplinas']??[])->keyBy('id');
        $selecionadas=array_map(function($id) use($visiveis,$salvas,$opcoes) {
            $valida=in_array($id,array_column($opcoes,'id'),true);
            return ['id'=>$id,'nome'=>$visiveis[$id]->nome??$salvas[$id]['nome']??'Disciplina indisponível','elegivel'=>$valida,
                'motivo'=>$valida?null:'Confira a disciplina, as turmas e as atribuições do vínculo atual.'];
        },$ids);
        return ApiResponse::success(['opcoes'=>$opcoes,'selecionadas'=>$selecionadas,'curriculo_versao'=>$resumo['versao']??0,'referencia_em'=>now('America/Sao_Paulo')->toDateString()]);
    }
    public function copiar(Request $request, int $id): JsonResponse
    {
        $dados = $request->validate(['titulo'=>'required|string|max:255','chave'=>'required|uuid']);
        $prova = app(\App\Services\DuplicarProva::class)->executar($request->user(),$id,trim($dados['titulo']),strtolower($dados['chave']));
        return ApiResponse::success(['id'=>$prova->id],null,201);
    }

    /** Impede criar/editar prova em escola ou turma fora do escopo do usuário. */
    private function foraDoEscopo($usuario, int $escolaId, array $turmaIds): ?JsonResponse
    {
        if (EspacoPessoal::doAtor($usuario)) {
            return Escopo::turmas($usuario)->whereIn('id', $turmaIds)->count() === count(array_unique($turmaIds))
                ? null : ApiResponse::notFound('Turma não encontrada.');
        }
        $escolas = Escopo::escolaIds($usuario);
        if ($escolas !== null && !in_array($escolaId, $escolas, true)) {
            return Espacos::ativo() ? ApiResponse::notFound('Escola não encontrada.') : ApiResponse::error('Escola fora do seu escopo.', [], 403);
        }

        if (Espacos::ativo()) {
            $espaco = Espacos::daEscola($usuario, $escolaId);
            if (DB::table('turmas')->whereIn('id', $turmaIds)->where('espaco_id', $espaco)->count() !== count(array_unique($turmaIds))) {
                return ApiResponse::notFound('Turma não encontrada.');
            }
        }
        $turmas = Escopo::turmaIds($usuario);
        $invalidas = DB::table('turmas')->whereIn('id', $turmaIds)
            ->where(fn ($q) => $q->where('escola_id', '!=', $escolaId)
                ->when($turmas !== null, fn ($q2) => $q2->orWhereNotIn('id', $turmas ?: [0])))
            ->count();
        if ($invalidas > 0) {
            return ApiResponse::error('Turma fora do seu escopo ou de outra escola.', [], 403);
        }

        return null;
    }

    private function progresso(ConsultasPedagogicas $consulta, Prova $p): array
    {
        if ($p->status === 'corrigida') {
            $totalCartoes = $consulta->tabela('cartoes')->where('prova_id', $p->id)->when(Espacos::ativo(), fn ($q) => $q->where('espaco_id', $p->espaco_id))->count();
            $lidos        = $consulta->tabela('cartoes')->where('prova_id', $p->id)->when(Espacos::ativo(), fn ($q) => $q->where('espaco_id', $p->espaco_id))->whereIn('status', ['lido', 'ambiguo'])->count();
            $media        = $consulta->tabela('notas')->where('prova_id', $p->id)->when(Espacos::ativo(), fn ($q) => $q->where('espaco_id', $p->espaco_id))->avg('nota_final');

            return [
                'tipo'          => 'media',
                'media'         => $media !== null ? round((float) $media, 1) : null,
                'cartoes_lidos' => $lidos,
                'cartoes_total' => $totalCartoes,
            ];
        }

        if ($p->status === 'em_correcao') {
            $totalCartoes = $consulta->tabela('cartoes')->where('prova_id', $p->id)->when(Espacos::ativo(), fn ($q) => $q->where('espaco_id', $p->espaco_id))->count();
            $lidos        = $consulta->tabela('cartoes')->where('prova_id', $p->id)->when(Espacos::ativo(), fn ($q) => $q->where('espaco_id', $p->espaco_id))->whereIn('status', ['lido', 'ambiguo'])->count();

            return ['tipo' => 'correcao', 'lidos' => $lidos, 'total' => $totalCartoes];
        }

        if ($p->status === 'rascunho') {
            $gabarito    = $consulta->tabela('gabaritos')->where('prova_id', $p->id)->when(Espacos::ativo(), fn ($q) => $q->where('espaco_id', $p->espaco_id))->first();
            $preenchidas = $gabarito ? $consulta->tabela('gabarito_questoes')->where('gabarito_id', $gabarito->id)->when(Espacos::ativo(), fn ($q) => $q->where('espaco_id', $p->espaco_id))->count() : 0;

            return ['tipo' => 'gabarito', 'preenchidas' => $preenchidas, 'total' => $p->num_questoes];
        }

        return ['tipo' => 'nenhum'];
    }

    public function index(Request $request): JsonResponse
    {
        $consulta = new ConsultasPedagogicas($request->user());
        $query = Escopo::provas($request->user());

        if ($busca = $request->query('busca')) {
            $query->where(function ($q) use ($busca) {
                $q->where('titulo', 'like', "%{$busca}%")
                  ->orWhere('disciplina', 'like', "%{$busca}%");
            });
        }

        if ($disciplina = $request->query('disciplina')) {
            $query->where('disciplina', $disciplina);
        }

        if ($status = $request->query('status')) {
            $query->where('status', $status);
        }

        $encontradas = $query->with(['escola:id,nome', 'autor:id,nome'])
            ->orderByDesc('created_at')
            ->get();
        $curriculos=config('disciplinas.ativo')?app(CurriculoProva::class)->resumos($request->user(),$encontradas->pluck('id')->all()):[];
        $provas=$encontradas->map(function (Prova $p) use ($consulta,$curriculos) {
                $turmasLista = $consulta->tabela('prova_turmas')
                    ->joinSub($consulta->tabela('turmas'), 'turmas', 'prova_turmas.turma_id', '=', 'turmas.id')
                    ->when(Espacos::ativo(), fn ($q) => $q->whereColumn('turmas.espaco_id', 'prova_turmas.espaco_id'))
                    ->where('prova_turmas.prova_id', $p->id)
                    ->when(Espacos::ativo(), fn ($q) => $q->where('prova_turmas.espaco_id', $p->espaco_id))
                    ->orderBy('turmas.nome')
                    ->get(['turmas.id', 'turmas.nome']);

                return array_merge($p->toArray(), [
                    'curriculo'=>$curriculos[$p->id]??null,
                    'criado_por_nome' => $p->autor?->nome,
                    'pode_editar' => Escopo::podeEditarProva($consulta->ator,$p),
                    'pode_copiar' => \App\Services\DuplicarProva::elegivel($consulta->ator,$p),
                    'escola_nome'  => $p->escola?->nome,
                    'turmas'       => $turmasLista->pluck('nome')->all(),
                    'turmas_lista' => $turmasLista->map(fn ($t) => ['id' => (int) $t->id, 'nome' => $t->nome])->all(),
                    'total_alunos' => $consulta->tabela('prova_turmas')
                        ->joinSub($consulta->tabela('alunos'), 'alunos', 'alunos.turma_id', '=', 'prova_turmas.turma_id')
                        ->when(Espacos::ativo(), fn ($q) => $q->whereColumn('alunos.espaco_id', 'prova_turmas.espaco_id'))
                        ->where('prova_turmas.prova_id', $p->id)
                        ->when(Espacos::ativo(), fn ($q) => $q->where('prova_turmas.espaco_id', $p->espaco_id))->where('alunos.ativo', true)->count(),
                    'cartoes_lidos' => $consulta->tabela('cartoes')->where('prova_id', $p->id)->when(Espacos::ativo(), fn ($q) => $q->where('espaco_id', $p->espaco_id))->where('status', 'lido')->count(),
                    'progresso'   => $this->progresso($consulta, $p),
                ]);
            });

        $baseQ = Escopo::provas($request->user());

        $kpis = [
            'total'      => $baseQ->count(),
            'rascunho'   => (clone $baseQ)->where('status', 'rascunho')->count(),
            'publicada'  => (clone $baseQ)->where('status', 'publicada')->count(),
            'correcao'   => (clone $baseQ)->where('status', 'em_correcao')->count(),
            'corrigida'  => (clone $baseQ)->where('status', 'corrigida')->count(),
        ];

        // Faceta do escopo completo, anterior aos filtros e à paginação da listagem.
        // Preserva o texto cadastrado; áreas/disciplinas não concedem acesso por si próprias.
        $disciplinasFiltro=(clone $baseQ)->reorder()->select('provas.disciplina')->whereNotNull('provas.disciplina')
            ->where('provas.disciplina','!=','')->distinct()->orderBy('provas.disciplina')->pluck('provas.disciplina')->all();
        return ApiResponse::success(['provas' => $provas, 'kpis' => $kpis, 'filtros'=>['disciplinas'=>$disciplinasFiltro]]);
    }

    public function store(Request $request): JsonResponse
    {
        $usuario = $request->user();
        $pessoal = EspacoPessoal::doAtor($usuario);
        $curricular=CurriculoProva::aplicavel($usuario);

        $data = $request->validate([
            'escola_id'       => $pessoal ? 'prohibited' : 'required|integer|exists:escolas,id',
            'titulo'          => 'required|string|max:255',
            'formato_avaliacao' => 'sometimes|in:gabarito',
            'disciplina'      => $curricular ? 'prohibited' : 'required|string|max:100',
            'disciplina_ids' => $curricular ? 'required|array|min:1|max:200' : 'prohibited',
            'disciplina_ids.*' => 'integer|min:1|distinct',
            'serie'           => 'required|string|max:50',
            'num_questoes'    => 'required|integer|min:1|max:100',
            'num_alternativas'=> 'required|integer|in:3,4,5',
            'nota_maxima'     => 'sometimes|numeric|min:0.5|max:100',
            'tipo_pontuacao'  => ['sometimes', Rule::in(['igual', 'personalizado'])],
            'anular_se_todas' => 'sometimes|boolean',
            'opcao_lingua'    => 'sometimes|boolean',
            'gerar_cartao_pdf'=> 'sometimes|boolean',
            'data_aplicacao'  => 'sometimes|date|nullable',
            'turma_ids'       => $curricular ? 'required|array|min:1|max:500' : 'sometimes|array|max:500',
            'turma_ids.*'     => 'integer|distinct|exists:turmas,id',
        ]);

        $turmaIds = array_map('intval',$data['turma_ids'] ?? []);
        $disciplinaIds=array_map('intval',$data['disciplina_ids']??[]);
        unset($data['turma_ids'],$data['disciplina_ids']);
        if ($curricular) $data['disciplina']='Configuração curricular';

        if ($erro = $this->foraDoEscopo($usuario, (int) ($data['escola_id'] ?? 0), $turmaIds)) {
            return $erro;
        }

        $data['criado_por'] = $usuario->id;
        $data['status']     = 'rascunho';

        $prova = app(CurriculoProva::class)->transacionar($usuario,function () use ($data, $usuario, $turmaIds, $pessoal,$curricular,$disciplinaIds) {
            $curriculo=app(CurriculoProva::class); $curriculo->travar($usuario);
            $prova = new Prova($data);
            $prova->forceFill(\App\Support\AutoriaProva::capturar($usuario));
            if (Espacos::ativo()) $prova->forceFill(Espacos::autoria($pessoal ?? Espacos::daEscola($usuario, (int) $data['escola_id']), $usuario));
            if ($pessoal) $prova->forceFill(['escola_id'=>null, 'espaco_tipo'=>'pessoal']);
            $prova->save();
            if ($curricular) {
                $prova->disciplina=$curriculo->configurar($usuario,$prova,$turmaIds,$disciplinaIds,null,'criar');
                $prova->save();
            }

            if ($turmaIds) {
                $inserts = array_map(fn ($tid) => ['prova_id' => $prova->id, 'turma_id' => $tid] + (Espacos::ativo() ? Espacos::autoria((int) $prova->espaco_id, $usuario) : []), $turmaIds);
                DB::table('prova_turmas')->insert($inserts);
            }
            return $prova;
        });

        return ApiResponse::success($prova, null, 201);
    }

    public function show(Request $request, int $id): JsonResponse
    {
        // Mantém turmas, currículo e estado de preservação no mesmo retrato de leitura.
        return CurriculoProva::aplicavel($request->user())
            ? DB::transaction(fn()=>$this->detalhar($request,$id))
            : $this->detalhar($request,$id);
    }

    private function detalhar(Request $request, int $id): JsonResponse
    {
        $consulta = new ConsultasPedagogicas($request->user());
        $prova = Escopo::provas($request->user())->with(['escola:id,nome', 'autor:id,nome'])->find($id);

        if (!$prova) {
            return ApiResponse::notFound('Prova não encontrada.');
        }

        $turmas = $consulta->tabela('prova_turmas')
            ->joinSub($consulta->tabela('turmas'), 'turmas', 'prova_turmas.turma_id', '=', 'turmas.id')
            ->when(Espacos::ativo(), fn ($q) => $q->whereColumn('turmas.espaco_id', 'prova_turmas.espaco_id'))
            ->where('prova_turmas.prova_id', $id)
            ->when(Espacos::ativo(), fn ($q) => $q->where('prova_turmas.espaco_id', $prova->espaco_id))
            ->select('turmas.id', 'turmas.nome', 'turmas.serie')
            ->get();

        return ApiResponse::success(array_merge($prova->toArray(), [
            'organizacao_curricular'=>CurriculoProva::aplicavel($request->user())?app(CurriculoProva::class)->estado($prova):null,
            'curriculo'=>config('disciplinas.ativo')?(app(CurriculoProva::class)->resumos($request->user(),[$prova->id])[$prova->id]??null):null,
            'criado_por_nome' => $prova->autor?->nome,
            'pode_editar' => Escopo::podeEditarProva($request->user(),$prova),
            'pode_copiar' => \App\Services\DuplicarProva::elegivel($request->user(),$prova),
            'procedencia' => $this->procedencia($request, $prova),
            'escola_nome' => $prova->escola?->nome,
            'turmas'      => $turmas,
        ]));
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $prova = Escopo::provaEditavel($request->user(), $id);

        if (!$prova) {
            return ApiResponse::notFound('Prova não encontrada.');
        }

        $curricular=CurriculoProva::aplicavel($request->user());
        $alterarCurriculo=$curricular && ($request->has('turma_ids')||$request->has('disciplina_ids'));
        $data = $request->validate([
            'titulo'          => 'sometimes|string|max:255',
            'formato_avaliacao' => 'prohibited',
            'disciplina'      => $curricular ? 'prohibited' : 'sometimes|string|max:100',
            'disciplina_ids' => $alterarCurriculo ? 'required|array|min:1|max:200' : 'prohibited',
            'disciplina_ids.*' => 'integer|min:1|distinct',
            'curriculo_versao' => $alterarCurriculo ? 'required|integer|min:0' : 'prohibited',
            'serie'           => 'sometimes|string|max:50',
            'num_questoes'    => 'sometimes|integer|min:1|max:100',
            'num_alternativas'=> 'sometimes|integer|in:3,4,5',
            'nota_maxima'     => 'sometimes|numeric|min:0.5|max:100',
            'tipo_pontuacao'  => ['sometimes', Rule::in(['igual', 'personalizado'])],
            'anular_se_todas' => 'sometimes|boolean',
            'opcao_lingua'    => 'sometimes|boolean',
            'gerar_cartao_pdf'=> 'sometimes|boolean',
            'status'          => ['sometimes', Rule::in(['rascunho', 'publicada', 'em_correcao', 'corrigida'])],
            'data_aplicacao'  => 'sometimes|date|nullable',
            'turma_ids'       => $alterarCurriculo ? 'required|array|min:1|max:500' : 'sometimes|array|max:500',
            'turma_ids.*'     => 'integer|distinct|exists:turmas,id',
        ]);

        $turmaIds = isset($data['turma_ids'])?array_map('intval',$data['turma_ids']):null;
        $disciplinaIds=array_map('intval',$data['disciplina_ids']??[]); $versao=$data['curriculo_versao']??null;
        unset($data['turma_ids'],$data['disciplina_ids'],$data['curriculo_versao']);
        if ($turmaIds !== null && ($erro = $this->foraDoEscopo($request->user(), (int) $prova->escola_id, $turmaIds))) {
            return $erro;
        }
        app(CurriculoProva::class)->transacionar($request->user(),function () use ($prova, $id, $data, $turmaIds, $request,$alterarCurriculo,$disciplinaIds,$versao) {
            $curriculo=app(CurriculoProva::class); $curriculo->travar($request->user());
            // Serializa edição das associações e preserva autoria de vínculos mantidos.
            DB::table('provas')->where('id', $id)->lockForUpdate()->first();
            $prova=Escopo::provaEditavel($request->user(),$id); abort_unless($prova,404);
            abort_if(isset($data['status']) && $data['status'] !== 'rascunho' && DB::table('prova_duplicacoes')->where('prova_id',$id)->exists(),422,'Use a publicação do gabarito para publicar esta cópia.');
            if ($alterarCurriculo) $data['disciplina']=$curriculo->configurar($request->user(),$prova,$turmaIds,$disciplinaIds,(int)$versao,'editar');
            if ($turmaIds !== null) {
                $q = DB::table('prova_turmas')->where('prova_id', $id)
                    ->when(Espacos::ativo(), fn ($q) => $q->where('espaco_id', $prova->espaco_id));
                $existentes = (clone $q)->pluck('turma_id')->all();
                (clone $q)->whereNotIn('turma_id', $turmaIds)->delete();
                $novas = array_values(array_diff($turmaIds, $existentes));
                if ($novas) {
                    DB::table('prova_turmas')->insert(array_map(fn ($tid) => ['prova_id' => $id, 'turma_id' => $tid]
                        + (Espacos::ativo() ? Espacos::autoria((int) $prova->espaco_id, $request->user()) : []), $novas));
                }
            }
            if (isset($data['status']) && $prova->status==='rascunho' && $data['status']!=='rascunho') $curriculo->conferirPublicacao($request->user(),$prova,'editar');
            $prova->update($data);
            if (isset($data['status']) && $data['status']!=='rascunho') $curriculo->registrarPublicacao($request->user(),$prova);
        });

        return ApiResponse::success($prova->fresh(), null, 200);
    }

    public function publicar(Request $request, int $id): JsonResponse
    {
        $prova = Escopo::provaEditavel($request->user(), $id);

        if (!$prova) {
            return ApiResponse::notFound('Prova não encontrada.');
        }

        return app(CurriculoProva::class)->transacionar($request->user(),function() use($request,$id) {
            $curriculo=app(CurriculoProva::class); $curriculo->travar($request->user());
            DB::table('provas')->where('id',$id)->lockForUpdate()->firstOrFail();
            $prova=Escopo::provaEditavel($request->user(),$id); abort_unless($prova,404);
            if ($prova->status!=='rascunho') return ApiResponse::error('Apenas rascunhos podem ser publicados.',[],422);
            if (DB::table('prova_duplicacoes')->where('prova_id',$id)->exists()) return ApiResponse::error('Revise e publique o gabarito para publicar esta cópia.',[],422);
            $curriculo->conferirPublicacao($request->user(),$prova,'editar');
            $prova->update(['status'=>'publicada']);
            $curriculo->registrarPublicacao($request->user(),$prova);
            return ApiResponse::success(['status'=>'publicada']);
        });
    }

    public function destroy(Request $request, int $id): JsonResponse
    {
        $prova = Escopo::provaEditavel($request->user(), $id);

        if (!$prova) {
            return ApiResponse::notFound('Prova não encontrada.');
        }

        if ($prova->status !== 'rascunho') {
            return ApiResponse::error('Apenas rascunhos podem ser excluídos.', [], 422);
        }

        $excluida = DB::transaction(function () use ($id, $prova) {
            DB::table('provas')->where('id',$id)->lockForUpdate()->firstOrFail();
            if (DB::table('prova_duplicacoes')->where('prova_id',$id)->orWhere('origem_id',$id)->orWhere('raiz_id',$id)->exists()) return false;
            if (DB::table('prova_curriculos')->where('prova_id',$id)->exists()) return false;
            DB::table('prova_turmas')->where('prova_id', $id)->when(Espacos::ativo(), fn ($q) => $q->where('espaco_id', $prova->espaco_id))->delete();
            $prova->delete();
            return true;
        });
        if (!$excluida) return ApiResponse::error('Esta prova possui histórico registrado e deve ser preservada.', [],409);

        return ApiResponse::success(['excluido' => true]);
    }

    private function procedencia(Request $request, Prova $prova): ?array
    {
        $p = DB::table('prova_duplicacoes')->where('prova_id',$prova->id)->where('espaco_id',$prova->espaco_id)->first();
        if (!$p) return null;
        $origem = Escopo::prova($request->user(),$p->origem_id);
        return ['origem_id'=>$origem?->id,'origem_titulo'=>$origem?->titulo,'copiada_em'=>$p->created_at,'contrato_versao'=>$p->contrato_versao];
    }
}
