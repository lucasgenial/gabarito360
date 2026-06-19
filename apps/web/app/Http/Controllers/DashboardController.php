<?php

namespace App\Http\Controllers;

use App\Services\ApiClient;
use Illuminate\View\View;

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

    public function index(): View
    {
        $perfil = session('auth_perfil');
        $view   = self::VIEWS[$perfil] ?? 'dashboard.admin';

        $dados = match ($perfil) {
            'secretario_educacao' => $this->dadosSecretaria(),
            'admin_rede'  => $this->dadosAdmin(),
            'dir_nucleo'  => $this->dadosDirNucleo(),
            'dir_escolar' => $this->dadosDirEscolar(),
            'coordenador' => $this->dadosCoordenador(),
            'professor'   => $this->dadosProfessor(),
            'aplicador'   => $this->dadosAplicador(),
            'aluno'       => $this->dadosAluno(),
            default       => [],
        };

        return view($view, array_merge([
            'nome'   => session('auth_nome'),
            'perfil' => $perfil,
        ], $dados));
    }

    private function dadosAdmin(): array
    {
        $resp = $this->api->get('/v1/dashboard/admin');

        if (!$resp->successful()) {
            return ['dashboard' => null];
        }

        return ['dashboard' => $resp->json('data')];
    }

    private function dadosDirNucleo(): array
    {
        $resp = $this->api->get('/v1/dashboard/dir-nucleo');

        if (!$resp->successful()) {
            return ['dashboard' => null];
        }

        return ['dashboard' => $resp->json('data')];
    }

    private function dadosAluno(): array
    {
        $resp = $this->api->get('/v1/dashboard/aluno');

        if (!$resp->successful()) {
            return ['dashboard' => null];
        }

        return ['dashboard' => $resp->json('data')];
    }

    private function dadosProfessor(): array
    {
        $resp = $this->api->get('/v1/dashboard/professor');

        if (!$resp->successful()) {
            return ['dashboard' => null];
        }

        return ['dashboard' => $resp->json('data')];
    }

    private function dadosCoordenador(): array
    {
        $resp = $this->api->get('/v1/dashboard/coordenador');

        if (!$resp->successful()) {
            return ['dashboard' => null];
        }

        return ['dashboard' => $resp->json('data')];
    }

    private function dadosDirEscolar(): array
    {
        $resp = $this->api->get('/v1/dashboard/dir-escolar');

        if (!$resp->successful()) {
            return ['dashboard' => null];
        }

        return ['dashboard' => $resp->json('data')];
    }

    private function dadosSecretaria(): array
    {
        $resp = $this->api->get('/v1/dashboard/secretaria');

        if (!$resp->successful()) {
            return ['dashboard' => null];
        }

        return ['dashboard' => $resp->json('data')];
    }

    private function dadosAplicador(): array
    {
        $resp = $this->api->get('/v1/dashboard/aplicador');

        if (!$resp->successful()) {
            return ['dashboard' => null];
        }

        return ['dashboard' => $resp->json('data')];
    }
}
