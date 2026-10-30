<?php

namespace App\Support;

/**
 * Arquivo: apps/web/app/Support/Acesso.php
 * Função: Menu por perfil e ações visíveis pelas permissões efetivas do usuário
 *         (docs/03 v2). Serve só à interface: quem decide o acesso aos dados é a API.
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
        'relatorios' => ['📈 Relatórios', null, ['relatorios.escola', 'relatorios.nucleo', 'relatorios.rede']],
    ];

    /** Relatório consolidado do nível de cada perfil (item "Relatórios" do menu). */
    private const RELATORIO_DO_PERFIL = [
        'admin_rede'  => 'relatorios.rede',
        'dir_nucleo'  => 'relatorios.nucleo',
        'dir_escolar' => 'relatorios.escola',
        'coordenador' => 'relatorios.escola',
    ];

    private const MENU = [
        'secretario_educacao' => ['painel'],
        'admin_rede'          => ['painel', 'provas', 'corrigir', 'turmas', 'alunos', 'escolas', 'equipe', 'relatorios'],
        'dir_nucleo'          => ['painel', 'provas', 'turmas', 'escolas', 'equipe', 'relatorios'],
        'dir_escolar'         => ['painel', 'provas', 'corrigir', 'turmas', 'alunos', 'escolas', 'equipe', 'relatorios'],
        'coordenador'         => ['painel', 'provas', 'corrigir', 'turmas', 'alunos', 'equipe', 'relatorios'],
        'professor'           => ['painel', 'provas', 'corrigir', 'turmas'],
        'aplicador'           => ['painel'],
        'aluno'               => ['painel'],
    ];


    /** Perfis que cada gestor pode cadastrar/gerir (espelha App\Support\Cadastro da API). */
    public const PODE_GERIR = [
        'admin_rede'  => ['admin_rede', 'dir_nucleo', 'dir_escolar', 'coordenador', 'professor', 'aplicador', 'aluno'],
        'dir_nucleo'  => ['dir_escolar', 'coordenador'],
        'dir_escolar' => ['coordenador', 'professor', 'aplicador', 'aluno'],
        'coordenador' => ['professor', 'aplicador', 'aluno'],
    ];

    public static function perfisGeridos(): array
    {
        return self::PODE_GERIR[self::perfil()] ?? [];
    }

    public static function perfil(): ?string
    {
        return session('auth_perfil');
    }

    public static function rotulo(?string $perfil = null): string
    {
        return self::ROTULOS[$perfil ?? self::perfil()] ?? 'Usuário';
    }

    /**
     * Permissões efetivas do usuário logado (padrão do perfil + extras − retiradas),
     * vindas da API no login. Sessões antigas (sem a lista) buscam uma vez em /auth/me.
     */
    public static function permissoes(): array
    {
        $lista = session('auth_permissoes');
        if (!is_array($lista) && session('auth_token')) {
            $resp  = app(\App\Services\ApiClient::class)->get('/v1/auth/me');
            $lista = $resp->successful() ? ($resp->json('data.permissoes') ?? []) : [];
            session(['auth_permissoes' => $lista]);
        }

        return is_array($lista) ? $lista : [];
    }

    /** Chave do catálogo (docs/03 v2), ex.: pode('cartao.enviar'). A API confere de novo. */
    public static function pode(string $chave): bool
    {
        return in_array($chave, self::permissoes(), true);
    }

    /** Pode enviar/excluir/alterar cartões, considerando a correção aberta ou fechada. */
    public static function podeAlterarCorrecao(bool $fechada): bool
    {
        return self::pode('correcao.alterar') && (!$fechada || self::pode('correcao.reabrir'));
    }

    /** @return array<int, array{rotulo: string, url: string, ativo: bool}> */
    public static function menu(): array
    {
        $atual = request()->route()?->getName() ?? '';

        return array_map(function (string $chave) use ($atual) {
            [$rotulo, $rota, $padroes] = self::ITENS[$chave];
            $rota ??= self::RELATORIO_DO_PERFIL[self::perfil()] ?? 'painel';

            return [
                'rotulo' => $rotulo,
                'url'    => route($rota),
                'ativo'  => collect($padroes)->contains(fn ($p) => \Illuminate\Support\Str::is($p, $atual)),
            ];
        }, self::MENU[self::perfil()] ?? ['painel']);
    }
}
