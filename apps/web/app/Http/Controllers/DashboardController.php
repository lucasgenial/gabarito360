<?php

namespace App\Http\Controllers;

use App\Services\ApiClient;
use Illuminate\View\View;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    private const VIEWS = [
        'secretario_educacao' => 'dashboard.secretaria',
        'admin_rede'  => 'dashboard.admin',
        'dir_nucleo'  => 'dashboard.diretor-nucleo',
        'dir_escolar' => 'dashboard.diretor-escolar',
        'coordenador' => 'dashboard.coordenador',
        'professor'   => 'dashboard.professor',
        'aplicador'   => 'dashboard.aplicador',
        'aluno'       => 'dashboard.aluno',
    ];

    public function __construct(private ApiClient $api) {}

    public function index(Request $request): View
    {
        $contexto = $request->validate(['escola_id' => 'sometimes|integer|min:1', 'nucleo_id' => 'sometimes|integer|min:1', 'aluno_id' => 'sometimes|integer|min:1', 'secretaria_id' => 'sometimes|integer|min:1']);
        $perfil = \App\Support\Acesso::perfil();
        $view   = self::VIEWS[$perfil] ?? 'dashboard.admin';

        $dados = match ($perfil) {
            'secretario_educacao' => $this->dadosSecretaria($contexto),
            'admin_rede'  => $this->dadosAdmin($contexto),
            'dir_nucleo'  => $this->dadosDirNucleo($contexto),
            'dir_escolar' => $this->dadosDirEscolar($contexto),
            'coordenador' => $this->dadosCoordenador($contexto),
            'professor'   => $this->dadosProfessor($contexto),
            'aplicador'   => $this->dadosAplicador($contexto),
            'aluno'       => $this->dadosAluno($contexto),
            default       => [],
        };

        if (!empty($dados['dashboard']['selecao'])) return view('dashboard.selecionar-contexto', $dados['dashboard']['selecao']);

        return view($view, array_merge([
            'nome'   => session('auth_nome'),
            'perfil' => $perfil,
        ], $dados));
    }

    private function dadosAdmin(array $contexto): array
    {
        $resp = $this->api->get('/v1/dashboard/admin', $contexto);

        if (!$resp->successful() && !$resp->json('data.selecao')) {
            return ['dashboard' => null];
        }

        return ['dashboard' => $resp->json('data')];
    }

    private function dadosDirNucleo(array $contexto): array
    {
        $resp = $this->api->get('/v1/dashboard/dir-nucleo', $contexto);

        if (!$resp->successful() && !$resp->json('data.selecao')) {
            return ['dashboard' => null];
        }

        return ['dashboard' => $resp->json('data')];
    }

    private function dadosAluno(array $contexto): array
    {
        $resp = $this->api->get('/v1/dashboard/aluno', $contexto);

        if (!$resp->successful() && !$resp->json('data.selecao')) {
            return ['dashboard' => null];
        }

        return ['dashboard' => $resp->json('data')];
    }

    private function dadosProfessor(array $contexto): array
    {
        $resp = $this->api->get('/v1/dashboard/professor', $contexto);

        if (!$resp->successful() && !$resp->json('data.selecao')) {
            return ['dashboard' => null];
        }

        return ['dashboard' => $resp->json('data')];
    }

    private function dadosCoordenador(array $contexto): array
    {
        $resp = $this->api->get('/v1/dashboard/coordenador', $contexto);

        if (!$resp->successful() && !$resp->json('data.selecao')) {
            return ['dashboard' => null];
        }

        return ['dashboard' => $resp->json('data')];
    }

    private function dadosDirEscolar(array $contexto): array
    {
        $resp = $this->api->get('/v1/dashboard/dir-escolar', $contexto);

        if (!$resp->successful() && !$resp->json('data.selecao')) {
            return ['dashboard' => null];
        }

        return ['dashboard' => $resp->json('data')];
    }

    private function dadosSecretaria(array $contexto): array
    {
        $resp = $this->api->get('/v1/dashboard/secretaria', $contexto);

        if (!$resp->successful() && !$resp->json('data.selecao')) {
            return ['dashboard' => null];
        }

        return ['dashboard' => $resp->json('data')];
    }

    private function dadosAplicador(array $contexto): array
    {
        $resp = $this->api->get('/v1/dashboard/aplicador', $contexto);

        if (!$resp->successful() && !$resp->json('data.selecao')) {
            return ['dashboard' => null];
        }

        return ['dashboard' => $resp->json('data')];
    }
}
