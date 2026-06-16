<?php

namespace App\Http\Controllers;

class DashboardController extends Controller
{
    private const VIEWS = [
        'admin_rede'  => 'dashboard.admin',
        'dir_nucleo'  => 'dashboard.diretor-nucleo',
        'dir_escolar' => 'dashboard.diretor-escolar',
        'coordenador' => 'dashboard.coordenador',
        'professor'   => 'dashboard.professor',
        'aluno'       => 'dashboard.aluno',
    ];

    public function index()
    {
        $perfil = session('auth_perfil');
        $view   = self::VIEWS[$perfil] ?? 'dashboard.admin';

        return view($view, [
            'nome'   => session('auth_nome'),
            'perfil' => $perfil,
        ]);
    }
}
