<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Responses\ApiResponse;
use App\Services\ContaUsuario;
use App\Models\Usuario;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use App\Support\Cadastro;
use App\Support\Permissao;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class UsuarioController extends Controller
{
    private function escopoQuery(Request $request)
    {
        $usuario = $request->user();

        $query = Usuario::query()->where('id', '!=', $usuario->id);

        if (in_array($usuario->perfil, ['dir_escolar', 'coordenador', 'dir_nucleo'], true)) {
            // Membros das escolas do escopo (diretor de núcleo: todas as escolas do núcleo)
            $escolas = \App\Support\Escopo::escolaIds($usuario) ?: [0];
            $query->whereIn('id', function ($sub) use ($escolas) {
                $sub->select('usuario_id')->from('usuario_escopos')
                    ->where('escopo_tipo', 'escola')->whereIn('escopo_id', $escolas);
            });
        } elseif ($usuario->perfil !== 'admin_rede') {
            $query->whereRaw('1 = 0');
        }

        return $query;
    }

    public function index(Request $request): JsonResponse
    {
        $query = $this->escopoQuery($request);

        if ($perfil = $request->query('perfil')) {
            $query->where('perfil', $perfil);
        }

        if ($busca = $request->query('busca')) {
            $query->where(function ($q) use ($busca) {
                $q->where('nome', 'like', "%{$busca}%")
                  ->orWhere('email', 'like', "%{$busca}%");
            });
        }

        $usuarios = $query
            ->select('id', 'nome', 'email', 'cpf', 'perfil', 'ativo', 'ultimo_acesso', 'created_at')
            ->orderBy('perfil')
            ->orderBy('nome')
            ->get();

        $totais = DB::table('usuarios')
            ->select('perfil', DB::raw('count(*) as total'))
            ->groupBy('perfil')
            ->pluck('total', 'perfil');

        return ApiResponse::success([
            'usuarios' => $usuarios,
            'totais'   => $totais,
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $gestor = $request->user();

        $data = $request->validate([
            'nome'                   => 'required|string|max:200',
            'email'                  => 'required|email|unique:usuarios,email',
            'cpf'                    => 'required|string|regex:/^\d{11}$/|unique:usuarios,cpf',
            'perfil'                 => ['required', Rule::in(['secretario_educacao','admin_rede','dir_nucleo','dir_escolar','coordenador','professor','aplicador','aluno'])],
            'ativo'                  => 'sometimes|boolean',
            'data_nascimento'        => 'sometimes|date|nullable',
            'telefone'               => 'sometimes|string|max:20|nullable',
            'data_ingresso'          => 'sometimes|date|nullable',
            'formacao_academica'     => 'sometimes|string|max:200|nullable',
            'especializacao'         => 'sometimes|string|max:200|nullable',
            'registro_profissional'  => 'sometimes|string|max:50|nullable',
            'observacoes'            => 'sometimes|string|nullable',
            'escola_id'              => 'sometimes|integer|exists:escolas,id|nullable',
            'nucleo_id'              => 'sometimes|integer|exists:nucleos,id|nullable',
            'turma_ids'              => 'sometimes|array',
            'turma_ids.*'            => 'integer|exists:turmas,id',
        ], [
            'cpf.regex'    => 'CPF deve conter 11 dígitos.',
            'email.unique' => 'E-mail já cadastrado.',
            'cpf.unique'   => 'CPF já cadastrado.',
        ]);

        if ($erro = $this->validarPerfilEEscopo($gestor, $data['perfil'], $data['escola_id'] ?? null, $data['nucleo_id'] ?? null)) {
            return $erro;
        }
        // Turmas do professor: só da escola dele e só por quem vincula professor à turma
        $turmaIds = $data['perfil'] === 'professor' ? array_values(array_unique($data['turma_ids'] ?? [])) : [];
        if ($turmaIds) {
            if (!Permissao::pode($gestor, 'turma.vincular_professor')) {
                return ApiResponse::error('Você não pode vincular professores a turmas.', [], 403);
            }
            $foraDaEscola = DB::table('turmas')->whereIn('id', $turmaIds)->where('escola_id', '!=', $data['escola_id'])->exists();
            if ($foraDaEscola) {
                return ApiResponse::error('As turmas precisam ser da escola do professor.', ['turma_ids' => ['Turma de outra escola.']], 422);
            }
        }

        $usuario = Usuario::create([
            'nome'                  => $data['nome'],
            'email'                 => $data['email'],
            'cpf'                   => $data['cpf'],
            'perfil'                => $data['perfil'],
            'password'              => Hash::make(Str::random(32)),
            'ativo'                 => $data['ativo'] ?? true,
            'data_nascimento'       => $data['data_nascimento'] ?? null,
            'telefone'              => $data['telefone'] ?? null,
            'data_ingresso'         => $data['data_ingresso'] ?? null,
            'formacao_academica'    => $data['formacao_academica'] ?? null,
            'especializacao'        => $data['especializacao'] ?? null,
            'registro_profissional' => $data['registro_profissional'] ?? null,
            'observacoes'           => $data['observacoes'] ?? null,
        ]);

        if (!empty($data['escola_id'])) {
            DB::table('usuario_escopos')->insert([
                'usuario_id'  => $usuario->id,
                'escopo_tipo' => 'escola',
                'escopo_id'   => $data['escola_id'],
                'created_at'  => now(),
            ]);
        }

        foreach ($turmaIds as $turmaId) {
            DB::table('usuario_escopos')->insert([
                'usuario_id' => $usuario->id, 'escopo_tipo' => 'turma', 'escopo_id' => $turmaId, 'created_at' => now(),
            ]);
        }

        if ($data['perfil'] === 'dir_nucleo' && !empty($data['nucleo_id'])) {
            DB::table('usuario_escopos')->insert([
                'usuario_id'  => $usuario->id,
                'escopo_tipo' => 'nucleo',
                'escopo_id'   => $data['nucleo_id'],
                'created_at'  => now(),
            ]);
        }

        if ($usuario->ativo) {
            app(ContaUsuario::class)->convidar($usuario, $gestor);
        }

        return ApiResponse::success($usuario, null, 201);
    }

    public function show(Request $request, int $id): JsonResponse
    {
        $usuario = $this->escopoQuery($request)->where('id', $id)->first();

        if (!$usuario) {
            return ApiResponse::notFound('Usuário não encontrado.');
        }

        $escolaId = DB::table('usuario_escopos')
            ->where('usuario_id', $id)
            ->where('escopo_tipo', 'escola')
            ->value('escopo_id');

        $secretariaId = DB::table('usuario_escopos')
            ->where('usuario_id', $id)
            ->where('escopo_tipo', 'secretaria')
            ->value('escopo_id');

        return ApiResponse::success(array_merge($usuario->toArray(), [
            'escola_id'     => $escolaId,
            'secretaria_id' => $secretariaId,
        ]));
    }

    /** Novo convite para quem ainda não criou a senha ou perdeu o link. */
    public function reenviarConvite(Request $request, int $id, ContaUsuario $conta): JsonResponse
    {
        $usuario = $this->escopoQuery($request)->where('id', $id)->first();
        if (!$usuario) {
            return ApiResponse::notFound('Usuário não encontrado.');
        }
        if (!$usuario->ativo) {
            return ApiResponse::error('Ative o usuário antes de enviar o convite.', [], 422);
        }

        $conta->convidar($usuario, $request->user());

        return ApiResponse::success(['mensagem' => 'Convite enviado.']);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $gestor  = $request->user();
        $usuario = $this->escopoQuery($request)->where('id', $id)->first();

        if (!$usuario) {
            return ApiResponse::notFound('Usuário não encontrado.');
        }
        if (!Cadastro::podeGerirPerfil($gestor, $usuario->perfil)) {
            return ApiResponse::error('Você não pode alterar um usuário deste perfil.', [], 403);
        }

        $data = $request->validate([
            'nome'                   => 'sometimes|string|max:200',
            'email'                  => ['sometimes', 'email', Rule::unique('usuarios', 'email')->ignore($id)],
            'perfil'                 => ['sometimes', Rule::in(['secretario_educacao','admin_rede','dir_nucleo','dir_escolar','coordenador','professor','aplicador','aluno'])],
            'ativo'                  => 'sometimes|boolean',
            'data_nascimento'        => 'sometimes|date|nullable',
            'telefone'               => 'sometimes|string|max:20|nullable',
            'data_ingresso'          => 'sometimes|date|nullable',
            'formacao_academica'     => 'sometimes|string|max:200|nullable',
            'especializacao'         => 'sometimes|string|max:200|nullable',
            'registro_profissional'  => 'sometimes|string|max:50|nullable',
            'observacoes'            => 'sometimes|string|nullable',
            'escola_id'              => 'sometimes|integer|exists:escolas,id|nullable',
            'nova_senha'             => 'sometimes|string|min:8|nullable',
            'forcar_troca_senha'     => 'sometimes|boolean',
        ]);

        $escolaId = $data['escola_id'] ?? null;
        unset($data['escola_id']);

        $perfilFinal = $data['perfil'] ?? $usuario->perfil;
        $escolaFinal = $request->has('escola_id') ? $escolaId
            : DB::table('usuario_escopos')->where('usuario_id', $id)->where('escopo_tipo', 'escola')->value('escopo_id');
        if (($request->has('perfil') || $request->has('escola_id'))
            && ($erro = $this->validarPerfilEEscopo($gestor, $perfilFinal, $escolaFinal ? (int) $escolaFinal : null, null, $usuario->perfil === 'dir_nucleo'))) {
            return $erro;
        }

        if (!empty($data['nova_senha'])) {
            $data['password'] = Hash::make($data['nova_senha']);
        }
        unset($data['nova_senha']);

        $usuario->update($data);

        if ($request->has('escola_id')) {
            DB::table('usuario_escopos')
                ->where('usuario_id', $id)
                ->where('escopo_tipo', 'escola')
                ->delete();

            if ($escolaId) {
                DB::table('usuario_escopos')->insert([
                    'usuario_id'  => $id,
                    'escopo_tipo' => 'escola',
                    'escopo_id'   => $escolaId,
                    'created_at'  => now(),
                ]);
            }
        }


        return ApiResponse::success($usuario->fresh());
    }

    public function toggle(Request $request, int $id): JsonResponse
    {
        $usuario = $this->escopoQuery($request)->where('id', $id)->first();

        if (!$usuario) {
            return ApiResponse::notFound('Usuário não encontrado.');
        }
        if (!Cadastro::podeGerirPerfil($request->user(), $usuario->perfil)) {
            return ApiResponse::error('Você não pode desativar um usuário deste perfil.', [], 403);
        }

        $novoStatus = !$usuario->ativo;
        $usuario->update(['ativo' => $novoStatus]);

        return ApiResponse::success(['ativo' => $novoStatus]);
    }

    /** "O que cada perfil pode": padrão e extras concedíveis de cada perfil (docs/03 v2). */
    public function catalogoPerfis(): JsonResponse
    {
        $descr = DB::table('permissoes')->get(['chave', 'modulo', 'descricao'])->keyBy('chave');
        $porPerfil = DB::table('perfil_permissoes')->orderBy('permissao')->get()->groupBy('perfil')
            ->map(fn ($linhas) => [
                'padrao' => $linhas->where('tipo', 'padrao')->pluck('permissao')->values(),
                'extra'  => $linhas->where('tipo', 'extra')->pluck('permissao')->values(),
            ]);

        return ApiResponse::success([
            'permissoes' => $descr->values(),
            'perfis'     => $porPerfil,
            'cadastra'   => Cadastro::PODE_GERIR,
        ]);
    }

    /**
     * Permissões de um usuário: padrão do perfil (e se foi retirada), extras concedidas,
     * o que quem está vendo pode definir, e o registro de mudanças (docs/03 §1).
     */
    public function permissoes(Request $request, int $id): JsonResponse
    {
        $gestor = $request->user();
        $alvo   = $id === $gestor->id ? $gestor : $this->escopoQuery($request)->where('id', $id)->first();
        if (!$alvo) {
            return ApiResponse::notFound('Usuário não encontrado.');
        }

        $pessoais = DB::table('usuario_permissoes as up')->leftJoin('usuarios as u', 'u.id', '=', 'up.definida_por')
            ->where('up.usuario_id', $alvo->id)
            ->get(['up.permissao', 'up.efeito', 'up.valida_ate', 'up.updated_at', 'u.nome as definida_por'])->keyBy('permissao');
        $tipos = DB::table('perfil_permissoes')->where('perfil', $alvo->perfil)->pluck('tipo', 'permissao');

        $itens = DB::table('permissoes')->orderBy('modulo')->orderBy('chave')->get(['chave', 'modulo', 'descricao'])
            ->filter(fn ($p) => isset($tipos[$p->chave]))
            ->map(function ($p) use ($tipos, $pessoais, $gestor, $alvo) {
                $pessoal = $pessoais->get($p->chave);
                $tipo    = $tipos[$p->chave];
                $efeito  = $tipo === 'padrao' ? 'retirar' : 'conceder';

                return [
                    'chave'        => $p->chave,
                    'modulo'       => $p->modulo,
                    'descricao'    => $p->descricao,
                    'tipo'         => $tipo,                                   // padrao | extra
                    'estado'       => $pessoal->efeito ?? 'padrao',            // padrao | conceder | retirar
                    'valida_ate'   => $pessoal->valida_ate ?? null,
                    'definida_por' => $pessoal->definida_por ?? null,
                    'definida_em'  => $pessoal->updated_at ?? null,
                    'pode_alterar' => $gestor->id !== $alvo->id && Permissao::podeDefinir($gestor, $alvo, $p->chave, $efeito) === null,
                ];
            })->values();

        $log = DB::table('usuario_permissoes_log as l')->leftJoin('usuarios as u', 'u.id', '=', 'l.feita_por')
            ->where('l.usuario_id', $alvo->id)->orderByDesc('l.id')->limit(30)
            ->get(['l.permissao', 'l.acao', 'l.valida_ate', 'l.created_at', 'u.nome as feita_por']);

        return ApiResponse::success([
            'usuario'  => ['id' => $alvo->id, 'nome' => $alvo->nome, 'perfil' => $alvo->perfil],
            'efetivas' => Permissao::efetivas($alvo),
            'itens'    => $itens,
            'historico' => $log,
        ]);
    }

    /**
     * Define extras/retiradas: itens = [{permissao, estado: padrao|conceder|retirar, valida_ate?}].
     * "padrao" desfaz a extra ou a retirada. Cada mudança é validada e registrada.
     */
    public function definirPermissoes(Request $request, int $id): JsonResponse
    {
        $gestor = $request->user();
        $alvo   = $this->escopoQuery($request)->where('id', $id)->first();
        if (!$alvo) {
            return ApiResponse::notFound('Usuário não encontrado.');
        }

        $data = $request->validate([
            'itens'              => 'required|array|min:1',
            'itens.*.permissao'  => 'required|string|exists:permissoes,chave',
            'itens.*.estado'     => ['required', Rule::in(['padrao', 'conceder', 'retirar'])],
            'itens.*.valida_ate' => 'sometimes|nullable|date|after_or_equal:today',
        ]);

        // 1) valida tudo antes de gravar qualquer coisa
        $erros = [];
        $mudancas = [];
        foreach ($data['itens'] as $item) {
            $chave  = $item['permissao'];
            $atual  = DB::table('usuario_permissoes')->where('usuario_id', $alvo->id)->where('permissao', $chave)->first();
            $estado = $item['estado'];
            if (($atual->efeito ?? 'padrao') === $estado && ($item['valida_ate'] ?? null) == ($atual->valida_ate ?? null)) {
                continue; // nada muda
            }
            // Para desfazer, vale a regra do efeito que existia
            $efeito = $estado === 'padrao' ? ($atual->efeito ?? 'conceder') : $estado;
            if ($motivo = Permissao::podeDefinir($gestor, $alvo, $chave, $efeito)) {
                $erros[$chave] = [$motivo];
                continue;
            }
            $mudancas[] = [$item, $atual];
        }
        if ($erros) {
            return ApiResponse::error('Algumas permissões não puderam ser definidas.', $erros, 403);
        }

        // 2) grava e registra
        DB::transaction(function () use ($mudancas, $gestor, $alvo) {
            foreach ($mudancas as [$item, $atual]) {
                $chave  = $item['permissao'];
                $estado = $item['estado'];
                if ($estado === 'padrao') {
                    DB::table('usuario_permissoes')->where('usuario_id', $alvo->id)->where('permissao', $chave)->delete();
                } else {
                    DB::table('usuario_permissoes')->updateOrInsert(
                        ['usuario_id' => $alvo->id, 'permissao' => $chave],
                        ['efeito' => $estado, 'valida_ate' => $item['valida_ate'] ?? null, 'definida_por' => $gestor->id,
                         'updated_at' => now(), 'created_at' => $atual->created_at ?? now()]
                    );
                }
                DB::table('usuario_permissoes_log')->insert([
                    'usuario_id' => $alvo->id, 'permissao' => $chave, 'acao' => $estado === 'padrao' ? 'remover' : $estado,
                    'valida_ate' => $item['valida_ate'] ?? null, 'feita_por' => $gestor->id, 'created_at' => now(),
                ]);
            }
        });
        Permissao::limparCache();

        return ApiResponse::success(['efetivas' => Permissao::efetivas($alvo)], 'Permissões atualizadas.');
    }

    /**
     * Perfil que o gestor pode atribuir e escola/núcleo dentro do escopo dele.
     * $semEscola: o usuário editado é de nível núcleo (não exige escola).
     */
    private function validarPerfilEEscopo($gestor, string $perfil, ?int $escolaId, ?int $nucleoId, bool $semEscola = false): ?JsonResponse
    {
        if (!Cadastro::podeGerirPerfil($gestor, $perfil)) {
            return ApiResponse::error('Você não pode cadastrar ou atribuir o perfil "' . $perfil . '".', [], 403);
        }
        if (in_array($perfil, Cadastro::DE_ESCOLA, true)) {
            if (!$escolaId) {
                return ApiResponse::error('Informe a escola do usuário.', ['escola_id' => ['Obrigatória para este perfil.']], 422);
            }
            if (!Cadastro::escolaNoEscopo($gestor, $escolaId)) {
                return ApiResponse::error('Escola fora do seu escopo.', [], 403);
            }
        }
        if ($perfil === 'dir_nucleo' && !$semEscola) {
            if (!$nucleoId || !Cadastro::nucleoNoEscopo($gestor, $nucleoId)) {
                return ApiResponse::error('Informe um núcleo válido para o diretor de núcleo.', ['nucleo_id' => ['Obrigatório.']], 422);
            }
        }

        return null;
    }
}
