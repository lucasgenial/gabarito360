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
     * Professor: próprias ou institucionais da coordenação/direção nas suas turmas.
     * Aplicador: só as provas da sua escola agendadas para hoje (sem conteúdo/gabarito).
     */
    public static function provas(Usuario $u): Builder { return self::consultarProvas($u,false); }

    /** Somente leitores históricos explicitamente classificados; nunca escrita ou envio OMR. */
    public static function provasHistoricas(Usuario $u): Builder { return self::consultarProvas($u,true); }

    private static function consultarProvas(Usuario $u,bool $historico): Builder
    {
        RegimeVinculos::exigir($u);
        $perfil = $u->perfil;
        $q = Espacos::filtrar(Prova::query(), $u, 'provas.espaco_id');
        // O consumidor de resultado individual será integrado antes de liberar provas ao aluno.
        if (ContextoVinculo::presente($u) && $perfil === 'aluno') return $q->whereRaw('1 = 0');
        Espacos::conferirEscola($q, 'provas');

        $escolas = self::escolaIds($u);
        if ($escolas !== null && !EspacoPessoal::doAtor($u)) {
            $q->whereIn('provas.escola_id', $escolas);
        }

        // Compartilhar turma não libera provas criadas por outro professor.
        if ($perfil === 'professor') {
            $turmas = self::turmaIds($u);
            $q->where(function ($w) use ($u,$turmas,$historico) {
                $w->where(function ($propria) use ($u) {
                    $propria->where('provas.criado_por',$u->id)
                        ->where(fn($perfil)=>$perfil->whereNull('provas.perfil_criacao')->orWhere('provas.perfil_criacao','professor'));
                })->orWhere(function ($institucional) use ($u,$turmas,$historico) {
                    $institucional->whereIn('provas.perfil_criacao',\App\Support\PerfisEscolares::INSTITUCIONAIS)
                        ->whereIn('provas.id', DB::table('prova_turmas')->whereIn('turma_id',$turmas ?: [0])
                            ->when(Espacos::ativo(),fn ($pt) => $pt->whereColumn('prova_turmas.espaco_id','provas.espaco_id')
                                ->whereExists(fn ($t) => $t->selectRaw('1')->from('turmas as associada')
                                    ->whereColumn('associada.id','prova_turmas.turma_id')->whereColumn('associada.espaco_id','prova_turmas.espaco_id')))
                            ->select('prova_id'));
                    if (\App\Services\CurriculoProva::aplicavel($u)) {
                        $v=ContextoVinculo::resolver($u); $hoje=now('America/Sao_Paulo')->toDateString();
                        $institucional->whereExists(function($c) use($v,$turmas,$hoje,$historico) {
                            $c->selectRaw('1')->from('prova_disciplinas as pd')
                                ->join('disciplinas as d',fn($j)=>$j->on('d.id','=','pd.disciplina_id')->on('d.espaco_id','=','pd.espaco_id'))
                                ->join('turma_disciplinas as td',fn($j)=>$j->on('td.disciplina_id','=','pd.disciplina_id')->on('td.espaco_id','=','pd.espaco_id'))
                                ->join('prova_turmas as pt',fn($j)=>$j->on('pt.prova_id','=','pd.prova_id')->on('pt.espaco_id','=','pd.espaco_id')->on('pt.turma_id','=','td.turma_id'))
                                ->join('turmas as t',fn($j)=>$j->on('t.id','=','td.turma_id')->on('t.espaco_id','=','td.espaco_id'))
                                ->whereColumn('pd.prova_id','provas.id')->whereColumn('pd.espaco_id','provas.espaco_id')
                                ->whereIn('td.turma_id',$turmas?:[0])->where('d.ativo',true)->where('td.ativo',true)
                                ->where(function($estado)use($historico,$hoje){
                                    $estado->where('t.ativo',true);
                                    if($historico)$estado->orWhere(fn($h)=>$h->where('t.ativo',false)
                                        ->whereIn('provas.status',['publicada','em_correcao','corrigida'])
                                        ->where(fn($d)=>$d->whereDate('provas.data_aplicacao','<=',$hoje)->orWhere(fn($n)=>$n->whereNull('provas.data_aplicacao')->whereIn('provas.status',['em_correcao','corrigida']))));
                                })
                                ->whereExists(fn($i)=>$i->selectRaw('1')->from('disciplina_professores as dp')->whereColumn('dp.espaco_id','pd.espaco_id')->whereColumn('dp.disciplina_id','pd.disciplina_id')->where('dp.vinculo_id',$v->id)->where('dp.ativo',true))
                                ->whereExists(fn($a)=>$a->selectRaw('1')->from('docente_atribuicoes as da')->whereColumn('da.espaco_id','td.espaco_id')->whereColumn('da.turma_disciplina_id','td.id')->where('da.vinculo_id',$v->id)->where('da.situacao','ativa')->where('da.inicio_em','<=',$hoje)->where(fn($f)=>$f->whereNull('da.fim_em')->orWhere('da.fim_em','>',$hoje)));
                        });
                    }
                });
            });
        } elseif ($perfil === 'professor_autonomo') {
            $q->where('provas.criado_por', $u->id);
        }

        if ($perfil === 'aplicador') {
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

    /** Visibilidade institucional não concede ao professor a edição da prova da gestão. */
    public static function podeEditarProva(Usuario $u, Prova $p): bool
    {
        return !in_array($u->perfil,['professor','professor_autonomo'],true)
            || ((int)$p->criado_por === (int)$u->id && !in_array($p->perfil_criacao,\App\Support\PerfisEscolares::INSTITUCIONAIS,true));
    }

    public static function provaEditavel(Usuario $u, int $id): ?Prova
    {
        $p=self::provaComConteudo($u,$id);
        return $p && self::podeEditarProva($u,$p) ? $p : null;
    }

    /** Prova com acesso a conteúdo (gabarito, notas, relatórios): nunca para o aplicador. */
    public static function provaComConteudo(Usuario $u, int $id): ?Prova
    {
        RegimeVinculos::exigir($u);
        return $u->perfil === 'aplicador' ? null : self::prova($u, $id);
    }

    public static function provaHistoricaComConteudo(Usuario $u,int $id): ?Prova
    {
        RegimeVinculos::exigir($u);
        return $u->perfil==='aplicador'?null:self::provasHistoricas($u)->find($id);
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
