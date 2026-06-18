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
            'nome'   => 'required|string|max:200',
            'email'  => 'required|email|unique:usuarios,email',
            'cpf'    => 'required|string|regex:/^\d{11}$/|unique:usuarios,cpf',
            'perfil' => ['required', Rule::in(['admin_rede','dir_nucleo','dir_escolar','coordenador','professor','aluno'])],
            'ativo'  => 'sometimes|boolean',
        ], [
            'cpf.regex'    => 'CPF deve conter 11 dígitos.',
            'email.unique' => 'E-mail já cadastrado.',
            'cpf.unique'   => 'CPF já cadastrado.',
        ]);

        $usuario = Usuario::create([
            'nome'     => $data['nome'],
            'email'    => $data['email'],
            'cpf'      => $data['cpf'],
            'perfil'   => $data['perfil'],
            'password' => Hash::make(Str::random(32)),
            'ativo'    => $data['ativo'] ?? true,
        ]);

        return ApiResponse::success(
            $usuario->only(['id', 'nome', 'email', 'perfil', 'ativo']),
            'Membro cadastrado com sucesso.',
            201
        );
    }

    public function show(Request $request, int $id): JsonResponse
    {
        $usuario = $this->escopoQuery($request)->where('id', $id)->first();

        if (!$usuario) {
            return ApiResponse::notFound('Usuário não encontrado.');
        }

        return ApiResponse::success($usuario);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $this->autorizarGestor($request);

        $usuario = $this->escopoQuery($request)->where('id', $id)->first();

        if (!$usuario) {
            return ApiResponse::notFound('Usuário não encontrado.');
        }

        $data = $request->validate([
            'nome'   => 'sometimes|string|max:200',
            'email'  => ['sometimes', 'email', Rule::unique('usuarios', 'email')->ignore($id)],
            'perfil' => ['sometimes', Rule::in(['admin_rede','dir_nucleo','dir_escolar','coordenador','professor','aluno'])],
            'ativo'  => 'sometimes|boolean',
        ]);

        $usuario->update($data);

        return ApiResponse::success(
            $usuario->fresh()->only(['id', 'nome', 'email', 'perfil', 'ativo']),
            'Membro atualizado com sucesso.'
        );
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

        return ApiResponse::success(
            ['ativo' => $novoStatus],
            $novoStatus ? 'Membro ativado.' : 'Membro desativado.'
        );
    }

    private function autorizarGestor(Request $request): void
    {
        if (!in_array($request->user()->perfil, ['admin_rede', 'dir_escolar'])) {
            abort(403, 'Sem permissão para gerenciar membros.');
        }
    }
}
