<?php

namespace App\Support;

/**
 * Arquivo: apps/web/app/Support/Acesso.php
 * Função: Menu e ações visíveis por perfil (docs/03-perfis-e-permissoes.md —
 *         "Navegação por Perfil" e matrizes de permissão). Serve só à interface:
 *         quem decide o acesso aos dados é a API.
 */
class Acesso
{
    public const ROTULOS = [
        'secretario_educacao' => 'Secretário de Educação',
        'admin_rede'          => 'Administrador da Rede',
        'dir_nucleo'          => 'Diretor de Núcleo',
        'dir_escolar'         => 'Diretor Escolar',
        'coordenador'         => 'Coordenador',
        'professor'           => 'Professor',
        'aplicador'           => 'Aplicador',
        'aluno'               => 'Aluno',
    ];

    /** Itens de menu: [rótulo, rota, padrões de rota que marcam o item como ativo]. */
    private const ITENS = [
        'painel'   => ['Painel', 'painel', ['painel']],
        'provas'   => ['Provas', 'provas.index', ['provas.*', 'relatorios.*', 'resultados.*']],
        'corrigir' => ['📷 Corrigir', 'correcao.index', ['correcao.*']],
        'turmas'   => ['Turmas', 'turmas.index', ['turmas.*']],
        'alunos'   => ['Alunos', 'alunos.index', ['alunos.*']],
        'escolas'  => ['Escolas', 'escolas.index', ['escolas.*']],
        'equipe'   => ['Equipe', 'membros.index', ['membros.*']],
    ];

    private const MENU = [
        'secretario_educacao' => ['painel'],
        'admin_rede'          => ['painel', 'provas', 'corrigir', 'turmas', 'alunos', 'escolas', 'equipe'],
        'dir_nucleo'          => ['painel', 'provas', 'turmas', 'escolas'],
        'dir_escolar'         => ['painel', 'provas', 'corrigir', 'turmas', 'alunos', 'escolas', 'equipe'],
        'coordenador'         => ['painel', 'provas', 'corrigir', 'turmas', 'alunos'],
        'professor'           => ['painel', 'provas', 'corrigir', 'turmas'],
        'aplicador'           => ['painel'],
        'aluno'               => ['painel'],
    ];

    /** Ações da interface → perfis que as veem (espelha as matrizes do doc 03). */
    private const ACOES = [
        'prova.criar'        => ['admin_rede', 'coordenador', 'professor'],
        'prova.editar'       => ['admin_rede', 'coordenador', 'professor'],
        'gabarito.ver'       => ['admin_rede', 'dir_nucleo', 'dir_escolar', 'coordenador', 'professor'],
        'cartoes.imprimir'   => ['admin_rede', 'dir_escolar', 'coordenador', 'professor'],
        'cartoes.enviar'     => ['admin_rede', 'dir_escolar', 'coordenador', 'professor', 'aplicador'],
        'correcao.ver'       => ['admin_rede', 'dir_nucleo', 'dir_escolar', 'coordenador', 'professor'],
        'correcao.resolver'  => ['admin_rede', 'dir_escolar', 'coordenador', 'professor'],
        'resultados.ver'     => ['admin_rede', 'dir_nucleo', 'dir_escolar', 'coordenador', 'professor'],
        'turma.gerenciar'    => ['admin_rede', 'dir_escolar', 'coordenador'],
        'aluno.gerenciar'    => ['admin_rede', 'dir_escolar', 'coordenador'],
        'equipe.gerenciar'   => ['admin_rede', 'dir_escolar'],
        'escola.configurar'  => ['admin_rede', 'dir_escolar'],
        'rede.configurar'    => ['admin_rede'],
    ];

    public static function perfil(): ?string
    {
        return session('auth_perfil');
    }

    public static function rotulo(?string $perfil = null): string
    {
        return self::ROTULOS[$perfil ?? self::perfil()] ?? 'Usuário';
    }

    public static function pode(string $acao): bool
    {
        return in_array(self::perfil(), self::ACOES[$acao] ?? [], true);
    }

    /** @return array<int, array{rotulo: string, url: string, ativo: bool}> */
    public static function menu(): array
    {
        $atual = request()->route()?->getName() ?? '';

        return array_map(function (string $chave) use ($atual) {
            [$rotulo, $rota, $padroes] = self::ITENS[$chave];

            return [
                'rotulo' => $rotulo,
                'url'    => route($rota),
                'ativo'  => collect($padroes)->contains(fn ($p) => \Illuminate\Support\Str::is($p, $atual)),
            ];
        }, self::MENU[self::perfil()] ?? ['painel']);
    }
}
