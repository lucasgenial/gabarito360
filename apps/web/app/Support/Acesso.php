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

    /**
     * Itens de menu: [rótulo, rótulo curto (barra do celular), ícone, rota, padrões de rota ativos,
     * permissões que liberam o item (basta uma; "a+b" exige as duas)]. O item só aparece com a permissão que a API
     * exige na tela de destino — assim nenhum item leva a 403 (docs/21, etapa 4).
     */
    private const ITENS = [
        'painel'     => ['Painel', 'Painel', 'casa', 'painel', ['painel'], []],
        'provas'     => ['Provas', 'Provas', 'prova', 'provas.index', ['provas.*', 'relatorios.prova', 'relatorios.turmaProva', 'resultados.*'], ['prova.ver+gabarito.ver']],
        'corrigir'   => ['📷 Corrigir', 'Corrigir', 'camera', 'correcao.index', ['correcao.*'], ['correcao.acompanhar']],
        'turmas'     => ['Turmas', 'Turmas', 'turma', 'turmas.index', ['turmas.*', 'relatorios.turma', 'relatorios.aluno'], ['turma.ver']],
        'alunos'     => ['Alunos', 'Alunos', 'aluno', 'alunos.index', ['alunos.*'], ['aluno.gerir', 'aluno.importar', 'aluno.desativar']],
        'escolas'    => ['Escolas', 'Escolas', 'escola', 'escolas.index', ['escolas.*'], ['escola.criar', 'escola.editar', 'escola.logo', 'nucleo.ver']],
        'equipe'     => ['Equipe', 'Equipe', 'equipe', 'membros.index', ['membros.*'], ['usuario.ver']],
        'relatorios' => ['📈 Relatórios', 'Relatórios', 'grafico', null, ['relatorios.escola', 'relatorios.nucleo', 'relatorios.rede'], ['relatorio.rede', 'relatorio.nucleo', 'relatorio.escola']],
    ];

    /** Relatório consolidado do item "Relatórios": o nível mais alto que a pessoa pode ver. */
    private const RELATORIO_POR_PERMISSAO = [
        'relatorio.rede'   => 'relatorios.rede',
        'relatorio.nucleo' => 'relatorios.nucleo',
        'relatorio.escola' => 'relatorios.escola',
    ];

    /** Ordem do menu no computador (até MAX_TOPO itens; o resto vai para "Mais"). */
    private const ORDEM = ['painel', 'provas', 'corrigir', 'turmas', 'relatorios', 'escolas', 'alunos', 'equipe'];

    private const MAX_TOPO = 6;

    /** Barra inferior do celular: os 4 itens principais de cada perfil (se a pessoa tiver acesso). */
    private const PRINCIPAIS = [
        'secretario_educacao' => ['painel', 'relatorios', 'escolas'],
        'admin_rede'          => ['painel', 'escolas', 'relatorios', 'equipe'],
        'dir_nucleo'          => ['painel', 'escolas', 'relatorios', 'equipe'],
        'dir_escolar'         => ['painel', 'provas', 'turmas', 'relatorios'],
        'coordenador'         => ['painel', 'corrigir', 'provas', 'turmas'],
        'professor'           => ['painel', 'corrigir', 'provas', 'turmas'],
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

    /**
     * Itens que a pessoa logada pode abrir, na ordem do menu.
     *
     * @return array<string, array{rotulo: string, curto: string, icone: string, url: string, ativo: bool}>
     */
    public static function itens(): array
    {
        $atual = request()->route()?->getName() ?? '';
        $itens = [];
        foreach (self::ORDEM as $chave) {
            [$rotulo, $curto, $icone, $rota, $padroes, $perms] = self::ITENS[$chave];
            if ($perms && !collect($perms)->contains(fn ($p) => collect(explode('+', $p))->every(fn ($c) => self::pode($c)))) {
                continue;
            }
            $rota ??= collect(self::RELATORIO_POR_PERMISSAO)->first(fn ($r, $p) => self::pode($p));
            $itens[$chave] = [
                'rotulo' => $rotulo,
                'curto'  => $curto,
                'icone'  => $icone,
                'url'    => route($rota),
                'ativo'  => collect($padroes)->contains(fn ($p) => \Illuminate\Support\Str::is($p, $atual)),
            ];
        }

        return $itens;
    }

    /**
     * Menu do computador: até MAX_TOPO itens no topo e o restante em "Mais" (docs/21: 6 + "Mais").
     *
     * @return array{topo: array, mais: array}
     */
    public static function menu(): array
    {
        $itens = array_values(self::itens());
        if (count($itens) <= self::MAX_TOPO) {
            return ['topo' => $itens, 'mais' => []];
        }

        return ['topo' => array_slice($itens, 0, self::MAX_TOPO), 'mais' => array_slice($itens, self::MAX_TOPO)];
    }

    /**
     * Barra inferior do celular: 4 itens (principais do perfil, completados na ordem do menu)
     * e, se sobrar algo, "Mais" com a lista completa.
     *
     * @return array{barra: array, mais: array}
     */
    public static function menuCelular(): array
    {
        $itens = self::itens();
        $barra = array_intersect_key($itens, array_flip(self::PRINCIPAIS[self::perfil()] ?? ['painel']));
        uksort($barra, fn ($a, $b) => array_search($a, self::PRINCIPAIS[self::perfil()] ?? []) <=> array_search($b, self::PRINCIPAIS[self::perfil()] ?? []));
        foreach ($itens as $chave => $item) {
            if (count($barra) >= 4) {
                break;
            }
            $barra[$chave] ??= $item;
        }
        $sobra = array_diff_key($itens, $barra);

        return ['barra' => array_values($barra), 'mais' => $sobra ? array_values($itens) : []];
    }
}
