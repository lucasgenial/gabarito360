<?php

namespace App\Http\Controllers;

use App\Services\ApiClient;
use Illuminate\Http\Request;
use Illuminate\View\View;

class MembroController extends Controller
{
    private const PERFIS = [
        'admin_rede'  => ['label' => 'Administrador da Rede', 'badge' => 'badge-info',    'ini' => 'A'],
        'dir_nucleo'  => ['label' => 'Diretor de Núcleo',     'badge' => 'badge-warn',    'ini' => 'N'],
        'dir_escolar' => ['label' => 'Diretor Escolar',       'badge' => 'badge-purple',  'ini' => 'D'],
        'coordenador' => ['label' => 'Coordenador Pedagógico','badge' => 'badge-amber',   'ini' => 'C'],
        'professor'   => ['label' => 'Professor',             'badge' => 'badge-info',    'ini' => 'P'],
        'aluno'       => ['label' => 'Aluno',                 'badge' => 'badge-success', 'ini' => 'AL'],
    ];

    public function __construct(private ApiClient $api) {}

    public function index(Request $request): View
    {
        $resp = $this->api->get('/v1/usuarios', $request->only('perfil', 'busca'));

        $usuarios = $resp->successful() ? $resp->json('data.usuarios') : [];
        $totais   = $resp->successful() ? $resp->json('data.totais')   : [];

        $agrupados = collect($usuarios)->groupBy('perfil');

        return view('membros.index', [
            'agrupados'  => $agrupados,
            'totais'     => $totais,
            'perfis'     => self::PERFIS,
            'filtroPerf' => $request->query('perfil', ''),
            'filtroBusc' => $request->query('busca', ''),
        ]);
    }

    public function create(): View
    {
        return view('membros.create', ['perfis' => self::PERFIS]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'nome'   => 'required|string|max:200',
            'email'  => 'required|email',
            'cpf'    => 'required|string',
            'perfil' => 'required|string',
        ]);

        $resp = $this->api->post('/v1/usuarios', [
            'nome'   => $request->nome,
            'email'  => $request->email,
            'cpf'    => preg_replace('/\D/', '', $request->cpf),
            'perfil' => $request->perfil,
            'ativo'  => $request->boolean('ativo', true),
        ]);

        if ($resp->failed()) {
            return back()->withInput()
                ->with('erro', $resp->json('message') ?? 'Erro ao cadastrar membro.');
        }

        return redirect()->route('membros.index')
            ->with('sucesso', 'Membro cadastrado com sucesso.');
    }

    public function edit(int $id): View
    {
        $resp = $this->api->get("/v1/usuarios/{$id}");

        if ($resp->failed()) {
            abort(404, 'Membro não encontrado.');
        }

        return view('membros.edit', [
            'membro' => $resp->json('data'),
            'perfis' => self::PERFIS,
        ]);
    }

    public function update(Request $request, int $id)
    {
        $request->validate([
            'nome'   => 'required|string|max:200',
            'email'  => 'required|email',
            'perfil' => 'required|string',
        ]);

        $resp = $this->api->put("/v1/usuarios/{$id}", [
            'nome'   => $request->nome,
            'email'  => $request->email,
            'perfil' => $request->perfil,
            'ativo'  => $request->boolean('ativo', true),
        ]);

        if ($resp->failed()) {
            return back()->withInput()
                ->with('erro', $resp->json('message') ?? 'Erro ao atualizar membro.');
        }

        return redirect()->route('membros.index')
            ->with('sucesso', 'Membro atualizado com sucesso.');
    }

    public function toggle(int $id)
    {
        $resp = $this->api->post("/v1/usuarios/{$id}/toggle");

        if ($resp->failed()) {
            return back()->with('erro', 'Não foi possível alterar o status do membro.');
        }

        $status = $resp->json('data.ativo') ? 'ativado' : 'desativado';

        return back()->with('sucesso', "Membro {$status} com sucesso.");
    }
}
