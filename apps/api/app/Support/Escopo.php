<?php

namespace App\Support;

use App\Models\Prova;
use App\Models\Usuario;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

/**
 * Arquivo: apps/api/app/Support/Escopo.php
 * Função: Escopo de dados por perfil (docs/03-perfis-e-permissoes.md, "Política de
 *         Isolamento"). Fonte única para escolas, turmas e provas visíveis a cada usuário.
 *         null = sem restrição (admin_rede); [] = nada visível.
 */
class Escopo
{
    /** Escolas visíveis ao usuário. */
    public static function escolaIds(Usuario $u): ?array
    {
        return match ($u->perfil) {
            'admin_rede' => null,
            'dir_nucleo' => DB::table('escolas')
                ->join('usuario_escopos', function ($j) use ($u) {
                    $j->on('escolas.nucleo_id', '=', 'usuario_escopos.escopo_id')
                      ->where('usuario_escopos.usuario_id', $u->id)
                      ->where('usuario_escopos.escopo_tipo', 'nucleo');
                })
                ->pluck('escolas.id')->all(),
            'dir_escolar', 'coordenador', 'aplicador' => self::escopos($u, 'escola'),
            // Professor: escola do cadastro + escolas das turmas em que está vinculado
            'professor' => array_values(array_unique(array_merge(
                self::escopos($u, 'escola'),
                DB::table('turmas')->whereIn('id', self::escopos($u, 'turma'))->pluck('escola_id')->all()
            ))),
            default => [],
        };
    }

    /** Turmas visíveis: professor só as vinculadas; demais, as turmas das suas escolas. */
    public static function turmaIds(Usuario $u): ?array
    {
        if ($u->perfil === 'professor') {
            return self::escopos($u, 'turma');
        }

        $escolas = self::escolaIds($u);

        return $escolas === null
            ? null
            : DB::table('turmas')->whereIn('escola_id', $escolas)->pluck('id')->all();
    }

    /**
     * Provas visíveis. Professor: só as que criou ou das turmas em que está vinculado.
     * Aplicador: só as provas da sua escola agendadas para hoje (sem conteúdo/gabarito).
     */
    public static function provas(Usuario $u): Builder
    {
        $q = Prova::query();

        $escolas = self::escolaIds($u);
        if ($escolas !== null) {
            $q->whereIn('provas.escola_id', $escolas);
        }

        if ($u->perfil === 'professor') {
            $turmas = self::escopos($u, 'turma');
            $q->where(function ($w) use ($u, $turmas) {
                $w->where('provas.criado_por', $u->id)
                  ->orWhereIn('provas.id', DB::table('prova_turmas')->whereIn('turma_id', $turmas ?: [0])->select('prova_id'));
            });
        }

        if ($u->perfil === 'aplicador') {
            $q->whereDate('provas.data_aplicacao', now()->toDateString())
              ->whereIn('provas.status', ['publicada', 'em_correcao', 'corrigida']);
        }

        return $q;
    }

    public static function prova(Usuario $u, int $id): ?Prova
    {
        return self::provas($u)->find($id);
    }

    /** Prova com acesso a conteúdo (gabarito, notas, relatórios): nunca para o aplicador. */
    public static function provaComConteudo(Usuario $u, int $id): ?Prova
    {
        return $u->perfil === 'aplicador' ? null : self::prova($u, $id);
    }

    private static function escopos(Usuario $u, string $tipo): array
    {
        return DB::table('usuario_escopos')
            ->where('usuario_id', $u->id)
            ->where('escopo_tipo', $tipo)
            ->pluck('escopo_id')->map(fn ($id) => (int) $id)->all();
    }
}
