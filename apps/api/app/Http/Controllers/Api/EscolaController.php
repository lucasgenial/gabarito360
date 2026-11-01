<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Responses\ApiResponse;
use App\Models\Escola;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use App\Support\Cabecalho;
use App\Support\Censo;
use App\Support\Escopo;
use App\Support\Espacos;
use App\Support\Instituicoes;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class EscolaController extends Controller
{
    private function escopoIds(Request $request): ?array
    {
        return Escopo::escolaIds($request->user());
    }

    public function index(Request $request): JsonResponse
    {
        $ids   = $this->escopoIds($request);
        $query = Escola::query();

        if ($ids !== null) {
            $query->whereIn('id', $ids);
        }

        if ($busca = $request->query('busca')) {
            $query->where(function ($q) use ($busca) {
                $q->where('nome', 'like', "%{$busca}%")
                  ->orWhere('cidade', 'like', "%{$busca}%")
                  ->orWhere('inep', 'like', "%{$busca}%");
            });
        }

        if ($request->has('ativo')) {
            $query->where('ativo', (bool) $request->query('ativo'));
        }

        $escolas = $query->with(['nucleo:id,nome', 'sede' => fn ($q) => $q->when($ids !== null, fn ($q) => $q->whereIn('id', $ids))->select('id', 'nome')])
            ->orderBy('nome')
            ->get()
            ->map(function (Escola $e) use ($request) {
                $totalTurmas = Espacos::agregadoEscolar(DB::table('turmas'), 'turmas', $request->user())->where('escola_id', $e->id)->count();

                $totalAlunosEscola = Espacos::agregadoEscolar(DB::table('alunos'), 'alunos', $request->user())
                    ->join('turmas', 'alunos.turma_id', '=', 'turmas.id')
                    ->where('turmas.escola_id', $e->id)
                    ->count();

                if ($request->user()->perfil === 'secretario_escolar') return array_merge($e->only(['id', 'nome', 'inep', 'ativo']), [
                    'total_turmas' => $totalTurmas, 'total_alunos' => $totalAlunosEscola,
                ]);

                $provas = Espacos::agregadoEscolar(DB::table('provas'), 'provas', $request->user())->where('escola_id', $e->id)->count();

                return array_merge($e->toArray(), [
                    'total_turmas' => $totalTurmas,
                    'total_alunos' => $totalAlunosEscola,
                    'total_provas' => $provas,
                    'nucleo_nome'  => $e->nucleo?->nome,
                    'sede_nome'    => $e->sede?->nome,
                ]);
            });

        // KPIs
        $baseQ = Escola::query();
        if ($ids !== null) $baseQ->whereIn('id', $ids);

        $totalEscolas  = $baseQ->count();
        $totalAtivas   = (clone $baseQ)->where('ativo', true)->count();
        $totalAlunos   = (int) Espacos::agregadoEscolar(DB::table('alunos'), 'alunos', $request->user())
            ->join('turmas', 'alunos.turma_id', '=', 'turmas.id')
            ->when($ids !== null, fn ($q) => $q->whereIn('turmas.escola_id', $ids))
            ->count();
        $totalTurmas   = (int) Espacos::agregadoEscolar(DB::table('turmas'), 'turmas', $request->user())
            ->when($ids !== null, fn ($q) => $q->whereIn('escola_id', $ids))
            ->where('ativo', true)
            ->count();

        return ApiResponse::success([
            'escolas' => $escolas,
            'kpis'    => [
                'total_escolas' => $totalEscolas,
                'total_ativas'  => $totalAtivas,
                'total_alunos'  => $totalAlunos,
                'total_turmas'  => $totalTurmas,
            ],
        ]);
    }

    private const MENSAGENS = [
        'inep.regex'   => 'O código INEP tem 8 dígitos (consulte no Censo Escolar).',
        'inep.unique'  => 'Já existe uma escola cadastrada com este código INEP.',
        'cep.regex'    => 'O CEP tem 8 dígitos.',
        'co_municipio.digits' => 'O código IBGE do município tem 7 dígitos.',
    ];

    /** Regras dos campos da escola (modelo do ECOAR / Censo Escolar). */
    private function regras(?int $id = null): array
    {
        $req = $id ? 'sometimes' : 'required';

        return [
            'nucleo_id'              => "{$req}|integer|exists:nucleos,id",
            'nome'                   => "{$req}|string|max:200",
            'inep'                   => [$req, 'regex:/^\d{8}$/', Rule::unique('escolas', 'inep')->ignore($id)],
            'tipo_rede'              => [$req, Rule::in(['estadual', 'municipal', 'federal', 'privada'])],
            'tipo'                   => ['sometimes', Rule::in(['sede', 'anexo', 'unidade_vinculada'])],
            'escola_sede_id'         => 'nullable|integer|exists:escolas,id',
            'situacao_funcionamento' => ['nullable', Rule::in(['ativa', 'paralisada', 'extinta'])],
            'localizacao'            => ['nullable', Rule::in(['urbana', 'rural'])],
            'orgao_regional'         => 'nullable|string|max:20',
            'logradouro'             => 'nullable|string|max:255',
            'numero'                 => 'nullable|string|max:20',
            'complemento'            => 'nullable|string|max:100',
            'bairro'                 => 'nullable|string|max:100',
            'cep'                    => ['nullable', 'regex:/^\d{8}$/'],
            'cidade'                 => 'nullable|string|max:100',
            'co_municipio'           => 'nullable|integer|digits:7',
            'uf'                     => 'nullable|string|size:2',
            'latitude'               => 'nullable|numeric|between:-90,90',
            'longitude'              => 'nullable|numeric|between:-180,180',
            'telefone'               => 'nullable|string|max:20',
            'email'                  => 'nullable|email|max:150',
            'turnos'                 => 'nullable|array',
            'turnos.*'               => Rule::in(array_keys(Escola::TURNOS)),
            'etapas'                 => 'nullable|array',
            'etapas.*'               => Rule::in(array_keys(Escola::ETAPAS)),
            'modalidades'            => 'nullable|array',
            'modalidades.*'          => Rule::in(array_keys(Escola::MODALIDADES)),
            'ativo'                  => 'sometimes|boolean',
        ];
    }

    /** Anexo/unidade vinculada precisa de uma escola sede do mesmo núcleo; sede não tem sede. */
    private function validarSede(Request $request, array &$data, ?Escola $atual = null): ?JsonResponse
    {
        $tipo = $data['tipo'] ?? $atual?->tipo ?? 'sede';
        if ($tipo === 'sede') {
            $data['escola_sede_id'] = null;
            return null;
        }
        $sedeId = $data['escola_sede_id'] ?? $atual?->escola_sede_id;
        $nucleo = (int) ($data['nucleo_id'] ?? $atual?->nucleo_id);
        $sede   = $sedeId ? $this->escolaNoEscopo($request, (int) $sedeId) : null;
        $ids    = Escopo::escolaIds($request->user());
        if (!$sede || $sede->tipo !== 'sede' || $sede->id === $atual?->id || ($ids !== null && !in_array($sede->id, $ids, true))) {
            return ApiResponse::error('Escolha a escola sede deste anexo.', ['escola_sede_id' => ['Escola sede inválida.']], 422);
        }
        if ($sede->nucleo_id !== $nucleo) {
            return ApiResponse::error('O anexo precisa ser do mesmo núcleo da escola sede.', ['escola_sede_id' => ['Núcleo diferente da sede.']], 422);
        }

        return null;
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate($this->regras(), self::MENSAGENS);

        // Diretor de núcleo só cadastra escola no próprio núcleo
        if ($erro = $this->nucleoForaDoEscopo($request, (int) $data['nucleo_id'])) {
            return $erro;
        }
        if ($erro = $this->validarSede($request, $data)) {
            return $erro;
        }

        // Escola encontrada no Censo: origem "censo" (a reimportação mantém os dados em dia)
        $censo = DB::table('censo_escolas')->where('codigo_inep', $data['inep'])->first(['ano_censo', 'orgao_regional']);
        $data['origem']    = $censo ? 'censo' : 'manual';
        $data['ano_censo'] = $censo?->ano_censo;
        $data['orgao_regional'] ??= $censo?->orgao_regional;

        $escola = DB::transaction(function () use ($data, $request) {
            $cliente = Espacos::ativo() ? Instituicoes::nucleosAtivos()->where('n.id', $data['nucleo_id'])->lockForUpdate()->value('en.cliente_id') : null;
            if (Espacos::ativo()) abort_unless($cliente, 404);
            $e = Escola::create($data);
            if (Espacos::ativo()) Instituicoes::criarEspaco('escola', $e->id, $e->nome, $cliente, $request->user());
            return $e;
        });
        $this->aprenderOrgaoRegional($escola);

        return ApiResponse::success($escola, null, 201);
    }

    /** O núcleo sem código de órgão regional passa a ter o da primeira escola do Censo cadastrada nele. */
    private function aprenderOrgaoRegional(Escola $escola): void
    {
        if ($escola->orgao_regional) {
            DB::table('nucleos')->where('id', $escola->nucleo_id)->whereNull('orgao_regional')
                ->update(['orgao_regional' => $escola->orgao_regional, 'updated_at' => now()]);
        }
    }

    public function show(Request $request, int $id): JsonResponse
    {
        $ids    = $this->escopoIds($request);
        $query  = Escola::with('nucleo:id,nome');

        if ($ids !== null) {
            $query->whereIn('id', $ids);
        }

        $escola = $query->find($id);

        if (!$escola) {
            return ApiResponse::notFound('Escola não encontrada.');
        }

        if ($request->user()->perfil === 'secretario_escolar') return ApiResponse::success($escola->only(['id', 'nome', 'inep', 'ativo']));

        $totalTurmas = Espacos::agregadoEscolar(DB::table('turmas'), 'turmas', $request->user())->where('escola_id', $id)->count();

        $totalAlunosEscola = Espacos::agregadoEscolar(DB::table('alunos'), 'alunos', $request->user())
            ->join('turmas', 'alunos.turma_id', '=', 'turmas.id')
            ->where('turmas.escola_id', $id)
            ->count();

        $provas = Espacos::agregadoEscolar(DB::table('provas'), 'provas', $request->user())->where('escola_id', $id)->count();

        $turmas = Espacos::agregadoEscolar(DB::table('turmas'), 'turmas', $request->user())
            ->where('escola_id', $id)
            ->orderBy('nome')
            ->select('id', 'nome', 'ano_letivo as ano', 'turno', 'ativo')
            ->get()
            ->map(function ($t) use ($request) {
                return [
                    'id'           => $t->id,
                    'nome'         => $t->nome,
                    'ano'          => $t->ano,
                    'turno'        => $t->turno,
                    'ativo'        => (bool) $t->ativo,
                    'total_alunos' => Espacos::agregadoEscolar(DB::table('alunos'), 'alunos', $request->user())->where('turma_id', $t->id)->where('ativo', true)->count(),
                ];
            });

        if (\App\Support\ContextoVinculo::presente($request->user())) {
            $equipe = \App\Support\GestaoVinculos::pessoas($request->user(), $id)->orderBy('usuarios.nome')->get();
        } else {
            $equipe = \App\Models\Usuario::query()
                ->join('usuario_escopos', function ($j) use ($id) {
                    $j->on('usuarios.id', '=', 'usuario_escopos.usuario_id')
                      ->where('usuario_escopos.escopo_tipo', 'escola')
                      ->where('usuario_escopos.escopo_id', $id);
                })
                ->select('usuarios.id', 'usuarios.nome', 'usuarios.email', 'usuarios.perfil', 'usuarios.ativo')
                ->orderBy('usuarios.nome')
                ->get();
        }

        $provasLista = Espacos::agregadoEscolar(DB::table('provas'), 'provas', $request->user())
            ->where('escola_id', $id)
            ->orderByDesc('data_aplicacao')
            ->select('id', 'titulo', 'disciplina', 'status', 'data_aplicacao')
            ->get()
            ->map(function ($p) use ($request) {
                $turmasNomes = Espacos::agregadoEscolar(DB::table('prova_turmas'), 'prova_turmas', $request->user())
                    ->join('turmas', 'prova_turmas.turma_id', '=', 'turmas.id')
                    ->where('prova_turmas.prova_id', $p->id)
                    ->pluck('turmas.nome')
                    ->implode(', ');

                $totalAlunos = Espacos::agregadoEscolar(DB::table('prova_turmas'), 'prova_turmas', $request->user())
                    ->join('alunos', 'alunos.turma_id', '=', 'prova_turmas.turma_id')
                    ->where('prova_turmas.prova_id', $p->id)
                    ->where('alunos.ativo', true)
                    ->count();

                $media = Espacos::agregadoEscolar(DB::table('notas'), 'notas', $request->user())->where('prova_id', $p->id)->avg('nota_final');

                return [
                    'id'             => $p->id,
                    'titulo'         => $p->titulo,
                    'disciplina'     => $p->disciplina,
                    'turma'          => $turmasNomes ?: '—',
                    'data_aplicacao' => $p->data_aplicacao,
                    'total_alunos'   => $totalAlunos,
                    'media'          => $media !== null ? round((float) $media, 1) : null,
                    'status'         => $p->status,
                ];
            });

        $alunosLista = Espacos::agregadoEscolar(DB::table('alunos'), 'alunos', $request->user())
            ->join('turmas', 'alunos.turma_id', '=', 'turmas.id')
            ->where('turmas.escola_id', $id)
            ->select('alunos.id', 'alunos.nome', 'alunos.matricula', 'alunos.ativo', 'turmas.nome as turma')
            ->orderBy('alunos.nome')
            ->get()
            ->map(function ($a) use ($request) {
                $media = Espacos::agregadoEscolar(DB::table('notas'), 'notas', $request->user())->where('aluno_id', $a->id)->avg('nota_final');
                $totalProvas = Espacos::agregadoEscolar(DB::table('notas'), 'notas', $request->user())->where('aluno_id', $a->id)->count();

                return [
                    'id'           => $a->id,
                    'nome'         => $a->nome,
                    'matricula'    => $a->matricula,
                    'turma'        => $a->turma,
                    'media_geral'  => $media !== null ? round((float) $media, 1) : null,
                    'total_provas' => $totalProvas,
                    'ativo'        => (bool) $a->ativo,
                ];
            });

        $rede = DB::table('nucleos')->join('redes', 'redes.id', '=', 'nucleos.rede_id')
            ->where('nucleos.id', $escola->nucleo_id)->first(['redes.id', 'redes.nome']);

        $anexos = Escola::where('escola_sede_id', $id)->when($ids !== null, fn ($q) => $q->whereIn('id', $ids))->orderBy('nome')
            ->get(['id', 'nome', 'inep', 'tipo', 'cidade', 'ativo'])
            ->filter(fn ($a) => $ids === null || in_array($a->id, $ids, true))->values();

        return ApiResponse::success(array_merge($escola->toArray(), [
            'sede'      => $escola->escola_sede_id ? $this->escolaNoEscopo($request, (int) $escola->escola_sede_id)?->only(['id', 'nome', 'inep']) : null,
            'anexos'    => $anexos,
            'rede_id'   => $rede?->id,
            'rede_nome' => $rede?->nome,
            'total_turmas' => $totalTurmas,
            'total_alunos' => $totalAlunosEscola,
            'total_provas' => $provas,
            'nucleo_nome'  => $escola->nucleo?->nome,
            'turmas'       => $turmas,
            'equipe'       => $equipe,
            'provas'       => $provasLista,
            'alunos'       => $alunosLista,
        ]));
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $escola = $this->escolaNoEscopo($request, $id);

        if (!$escola) {
            return ApiResponse::notFound('Escola não encontrada.');
        }

        $data = $request->validate($this->regras($id), self::MENSAGENS);

        // Quem não cadastra escolas (diretor escolar) edita os dados da própria escola,
        // mas não muda INEP, núcleo, vínculo sede/anexo nem ativação
        if (!\App\Support\Permissao::pode($request->user(), 'escola.criar')) {
            unset($data['nucleo_id'], $data['ativo'], $data['inep'], $data['tipo'], $data['escola_sede_id']);
        }
        if (isset($data['nucleo_id']) && ($erro = $this->nucleoForaDoEscopo($request, (int) $data['nucleo_id']))) {
            return $erro;
        }
        if (Espacos::ativo() && isset($data['nucleo_id'])) abort_unless((int) $data['nucleo_id'] === (int) $escola->nucleo_id, 422, 'Mudança de núcleo exige procedimento próprio.');
        // Sede com anexos não pode virar anexo
        if (($data['tipo'] ?? 'sede') !== 'sede' && $escola->anexos()->exists()) {
            return ApiResponse::error('Esta escola tem anexos; ela precisa continuar como sede.', ['tipo' => ['Escola com anexos.']], 422);
        }
        if ((isset($data['tipo']) || array_key_exists('escola_sede_id', $data)) && ($erro = $this->validarSede($request, $data, $escola))) {
            return $erro;
        }

        // INEP trocado (ex.: "PENDENTE" → código real) e achado no Censo: passa a ser do Censo
        if (isset($data['inep']) && $data['inep'] !== $escola->inep) {
            $censo = DB::table('censo_escolas')->where('codigo_inep', $data['inep'])->first(['ano_censo', 'orgao_regional']);
            $data['origem']    = $censo ? 'censo' : 'manual';
            $data['ano_censo'] = $censo?->ano_censo;
            $data['orgao_regional'] ??= $censo?->orgao_regional;
        }
        // Edição à mão de escola do Censo: a reimportação não sobrescreve mais (regra do ECOAR)
        if (($data['origem'] ?? $escola->origem) === 'censo') {
            $data['edicao_manual'] = true;
        }

        $escola->update($data);
        $this->aprenderOrgaoRegional($escola->fresh());

        return ApiResponse::success($escola->fresh());
    }

    public function toggle(Request $request, int $id): JsonResponse
    {
        $escola = $this->escolaNoEscopo($request, $id);

        if (!$escola) {
            return ApiResponse::notFound('Escola não encontrada.');
        }

        $escola->update(['ativo' => !$escola->ativo]);

        return ApiResponse::success(
            ['ativo' => $escola->ativo],
            $escola->ativo ? 'Escola ativada.' : 'Escola desativada.'
        );
    }

    private function escolaNoEscopo(Request $request, int $id): ?Escola
    {
        $ids = Escopo::escolaIds($request->user());

        return ($ids === null || in_array($id, $ids, true)) ? Escola::find($id) : null;
    }

    private function nucleoForaDoEscopo(Request $request, int $nucleoId): ?JsonResponse
    {
        $ids = Instituicoes::nucleosIds($request->user());
        return $ids === null || in_array($nucleoId, $ids, true) ? null
            : (Espacos::ativo() ? ApiResponse::notFound('Núcleo não encontrado.') : ApiResponse::error('Você só pode cadastrar escolas no seu núcleo.', [], 403));
    }

    /** Logo da escola, após autorização institucional. */
    public function logo(Request $request, int $id)
    {
        $ids = Instituicoes::escolasLogoIds($request->user());
        $escola = Escola::query()->when($ids !== null, fn ($q) => $q->whereIn('id', $ids))->find($id);
        if (!$escola) return ApiResponse::notFound('Escola não encontrada.');
        $caminho = Cabecalho::logoPath($escola->logo_path);
        if (!$caminho) {
            return ApiResponse::notFound('Escola sem logo.');
        }

        return response()->file($caminho, ['Cache-Control' => 'private, no-store']);
    }

    /** Envia/troca o logo da escola (PNG/JPG até 2 MB). */
    public function enviarLogo(Request $request, int $id): JsonResponse
    {
        $escolas = Escopo::escolaIds($request->user());
        $escola  = DB::table('escolas')->where('id', $id)->first(['id', 'logo_path']);
        if (!$escola || ($escolas !== null && !in_array($id, $escolas, true))) {
            return ApiResponse::notFound('Escola não encontrada.');
        }

        $request->validate(['logo' => 'required|image|mimes:png,jpg,jpeg,webp|max:2048']);
        $novo = $request->file('logo')->store('escolas/'.$id.'/logos', 'public');
        if ($escola->logo_path) {
            Storage::disk('public')->delete($escola->logo_path);
        }
        DB::table('escolas')->where('id', $id)->update(['logo_path' => $novo, 'updated_at' => now()]);

        return ApiResponse::success(['tem_logo' => true]);
    }

    /** Logo do ente da rede ou da mantenedora. */
    public function logoRede(Request $request, int $redeId)
    {
        $ids = Instituicoes::redesIds($request->user());
        abort_unless($ids === null || in_array($redeId, $ids, true), 404);
        $caminho = Cabecalho::logoPath(DB::table('redes')->where('id', $redeId)->value('logo_path'));
        if (!$caminho) {
            return ApiResponse::notFound('Rede sem logo.');
        }

        return response()->file($caminho, ['Cache-Control' => 'private, no-store']);
    }

    /** Envia/troca o logo do ente: admin da rede ou diretor de núcleo de um núcleo da rede. */
    public function enviarLogoRede(Request $request, int $redeId): JsonResponse
    {
        $usuario = $request->user();
        $redes = Instituicoes::redesIds($usuario);
        abort_unless($redes === null || in_array($redeId, $redes, true), 404);
        $rede    = DB::table('redes')->where('id', $redeId)->first(['id', 'logo_path']);
        $permite = $usuario->perfil === 'admin_rede' || ($usuario->perfil === 'dir_nucleo' && DB::table('nucleos')
            ->whereIn('id', Instituicoes::referencias($usuario, 'nucleo'))->where('rede_id', $redeId)->exists());
        if (!$rede || !$permite) {
            return ApiResponse::notFound('Rede não encontrada.');
        }

        $request->validate(['logo' => 'required|image|mimes:png,jpg,jpeg,webp|max:2048']);
        $novo = $request->file('logo')->store('redes/'.$redeId.'/logos', 'public');
        if ($rede->logo_path) {
            Storage::disk('public')->delete($rede->logo_path);
        }
        DB::table('redes')->where('id', $redeId)->update(['logo_path' => $novo, 'updated_at' => now()]);

        return ApiResponse::success(['tem_logo' => true]);
    }
}
