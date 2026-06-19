<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Responses\ApiResponse;
use App\Models\Usuario;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
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

        if ($usuario->perfil === 'dir_escolar') {
            $escolaId = DB::table('usuario_escopos')
                ->where('usuario_id', $usuario->id)
                ->where('escopo_tipo', 'escola')
                ->value('escopo_id');

            $query->whereIn('id', function ($sub) use ($escolaId) {
                $sub->select('usuario_id')
                    ->from('usuario_escopos')
                    ->where('escopo_tipo', 'escola')
                    ->where('escopo_id', $escolaId);
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
        $this->autorizarGestor($request);

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
            'secretaria_id'          => 'sometimes|integer|exists:secretarias,id|nullable',
        ], [
            'cpf.regex'    => 'CPF deve conter 11 dígitos.',
            'email.unique' => 'E-mail já cadastrado.',
            'cpf.unique'   => 'CPF já cadastrado.',
        ]);

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

        if (!empty($data['secretaria_id'])) {
            DB::table('usuario_escopos')->insert([
                'usuario_id'  => $usuario->id,
                'escopo_tipo' => 'secretaria',
                'escopo_id'   => $data['secretaria_id'],
                'created_at'  => now(),
            ]);
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

    public function update(Request $request, int $id): JsonResponse
    {
        $this->autorizarGestor($request);

        $usuario = $this->escopoQuery($request)->where('id', $id)->first();

        if (!$usuario) {
            return ApiResponse::notFound('Usuário não encontrado.');
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
            'secretaria_id'          => 'sometimes|integer|exists:secretarias,id|nullable',
            'nova_senha'             => 'sometimes|string|min:8|nullable',
            'forcar_troca_senha'     => 'sometimes|boolean',
        ]);

        $escolaId = $data['escola_id'] ?? null;
        unset($data['escola_id']);

        $secretariaId = $data['secretaria_id'] ?? null;
        unset($data['secretaria_id']);

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

        if ($request->has('secretaria_id')) {
            DB::table('usuario_escopos')
                ->where('usuario_id', $id)
                ->where('escopo_tipo', 'secretaria')
                ->delete();

            if ($secretariaId) {
                DB::table('usuario_escopos')->insert([
                    'usuario_id'  => $id,
                    'escopo_tipo' => 'secretaria',
                    'escopo_id'   => $secretariaId,
                    'created_at'  => now(),
                ]);
            }
        }

        return ApiResponse::success($usuario->fresh());
    }

    public function toggle(Request $request, int $id): JsonResponse
    {
        $this->autorizarGestor($request);

        $usuario = $this->escopoQuery($request)->where('id', $id)->first();

        if (!$usuario) {
            return ApiResponse::notFound('Usuário não encontrado.');
        }

        $novoStatus = !$usuario->ativo;
        $usuario->update(['ativo' => $novoStatus]);

        return ApiResponse::success(['ativo' => $novoStatus]);
    }

    private function autorizarGestor(Request $request): void
    {
        if (!in_array($request->user()->perfil, ['admin_rede', 'dir_escolar'])) {
            abort(403, 'Sem permissão para gerenciar membros.');
        }
    }
}
