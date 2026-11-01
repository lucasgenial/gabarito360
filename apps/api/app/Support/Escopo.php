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
 *         null = compatibilidade anterior ao corte T2; [] = nada visível.
 */
class Escopo
{
    /** Escolas visíveis ao usuário. */
    public static function escolaIds(Usuario $u): ?array
    {
        RegimeVinculos::exigir($u);
        if (ContextoVinculo::presente($u)) return ContextoVinculo::escolas($u);
        $ids = match ($u->perfil) {
            'admin_rede' => Espacos::ativo()
                ? DB::table('escolas')->join('nucleos', 'nucleos.id', '=', 'escolas.nucleo_id')
                    ->whereIn('nucleos.rede_id', self::escopos($u, 'rede'))->pluck('escolas.id')->all()
                : null,
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
        return Espacos::ativo() ? Espacos::escolasPermitidas($u, $ids ?? []) : $ids;
    }

    /** Turmas visíveis: professor só as vinculadas; demais, as turmas das suas escolas. */
    public static function turmaIds(Usuario $u): ?array
    {
        RegimeVinculos::exigir($u);
        if ($pessoal = EspacoPessoal::doAtor($u)) {
            return DB::table('turmas')->where('espaco_id', $pessoal)->where('espaco_tipo', 'pessoal')
                ->whereNull('escola_id')->pluck('id')->map(fn ($id) => (int) $id)->all();
        }
        // Aluno não recebe a turma inteira por ter vínculo individual.
        if (ContextoVinculo::presente($u) && $u->perfil === 'aluno') return [];
        if ($u->perfil === 'professor') {
            $q = DB::table('turmas')->whereIn('id', self::escopos($u, 'turma'));
            Espacos::conferirEscola($q, 'turmas');
            if (Espacos::ativo()) {
                $q->whereIn('escola_id', self::escolaIds($u));
                Espacos::filtrar($q, $u, 'espaco_id');
            }
            return $q->pluck('id')->map(fn ($id) => (int) $id)->all();
        }

        $escolas = self::escolaIds($u);

        return $escolas === null
            ? null
            : Espacos::filtrar(Espacos::conferirEscola(DB::table('turmas'), 'turmas')->whereIn('escola_id', $escolas), $u, 'espaco_id')->pluck('id')->map(fn ($id) => (int) $id)->all();
    }

    /**
     * Provas visíveis. Professor: só as que criou ou das turmas em que está vinculado.
     * Aplicador: só as provas da sua escola agendadas para hoje (sem conteúdo/gabarito).
     */
    public static function provas(Usuario $u): Builder
    {
        RegimeVinculos::exigir($u);
        $q = Espacos::filtrar(Prova::query(), $u, 'provas.espaco_id');
        // O consumidor de resultado individual será integrado antes de liberar provas ao aluno.
        if (ContextoVinculo::presente($u) && $u->perfil === 'aluno') return $q->whereRaw('1 = 0');
        Espacos::conferirEscola($q, 'provas');

        $escolas = self::escolaIds($u);
        if ($escolas !== null && !EspacoPessoal::doAtor($u)) {
            $q->whereIn('provas.escola_id', $escolas);
        }

        if ($u->perfil === 'professor') {
            $turmas = self::turmaIds($u);
            $q->where(function ($w) use ($u, $turmas) {
                $w->where('provas.criado_por', $u->id)
                  ->orWhereIn('provas.id', DB::table('prova_turmas')->whereIn('turma_id', $turmas ?: [0])
                      ->when(Espacos::ativo(), fn ($q) => $q->whereColumn('prova_turmas.espaco_id', 'provas.espaco_id')
                          ->whereExists(fn ($t) => $t->selectRaw('1')->from('turmas as associada')
                              ->whereColumn('associada.id', 'prova_turmas.turma_id')->whereColumn('associada.espaco_id', 'prova_turmas.espaco_id')))
                      ->select('prova_id'));
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
        RegimeVinculos::exigir($u);
        return self::provas($u)->find($id);
    }

    /** Prova com acesso a conteúdo (gabarito, notas, relatórios): nunca para o aplicador. */
    public static function provaComConteudo(Usuario $u, int $id): ?Prova
    {
        RegimeVinculos::exigir($u);
        return $u->perfil === 'aplicador' ? null : self::prova($u, $id);
    }

    public static function turmas(Usuario $u): Builder
    {
        RegimeVinculos::exigir($u);
        $q = Espacos::filtrar(\App\Models\Turma::query(), $u, 'turmas.espaco_id');
        $ids = self::turmaIds($u);
        return $ids === null ? $q : $q->whereIn('turmas.id', $ids);
    }

    public static function alunos(Usuario $u): Builder
    {
        RegimeVinculos::exigir($u);
        $q = Espacos::filtrar(\App\Models\Aluno::query(), $u, 'alunos.espaco_id');
        if (Espacos::ativo()) {
            $q->whereExists(fn ($t) => $t->selectRaw('1')->from('turmas as turma_dona')
                ->whereColumn('turma_dona.id', 'alunos.turma_id')->whereColumn('turma_dona.espaco_id', 'alunos.espaco_id'));
        }
        if (ContextoVinculo::presente($u) && $u->perfil === 'aluno') {
            return $q->whereIn('alunos.id', ContextoVinculo::referencias($u, 'aluno'));
        }
        $ids = self::turmaIds($u);
        return $ids === null ? $q : $q->whereIn('alunos.turma_id', $ids);
    }

    private static function escopos(Usuario $u, string $tipo): array
    {
        if (ContextoVinculo::presente($u)) return ContextoVinculo::referencias($u, $tipo);
        return DB::table('usuario_escopos')
            ->where('usuario_id', $u->id)
            ->where('escopo_tipo', $tipo)
            ->pluck('escopo_id')->map(fn ($id) => (int) $id)->all();
    }
}
