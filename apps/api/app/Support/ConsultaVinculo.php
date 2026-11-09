<?php

namespace App\Support;

use App\Models\Usuario;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;

/** Compilador interno de leituras contextuais. Só identidade e ramo; autoridade permanece no SQL. */
final class ConsultaVinculo
{
    private function __construct(private readonly Usuario $ator, private readonly string $perfil, private readonly string $tipo, private readonly int $espaco) {}

    public static function para(Usuario $ator): self
    {
        $v = ContextoVinculo::resolver($ator);
        return new self($ator, $v->perfil, $v->espaco->tipo, (int) $v->espaco_id);
    }

    private function contexto(): Builder
    {
        // O ramo PHP nunca conserva um poder: mudança de perfil/tipo invalida o ramo antigo no SQL.
        return PredicadosVinculo::contexto($this->ator, true, $this->tipo, $this->perfil)
            ->where('ce.tipo', $this->tipo)->where('cv.espaco_id', $this->espaco);
    }

    public function escolas(): Builder
    {
        $q = Espacos::escolas();
        $contexto = $this->contexto()->selectRaw('1')->whereColumn('ce.cliente_id', 'ee.cliente_id');
        match ($this->tipo) {
            'rede' => $contexto->whereColumn('ce.rede_id', 'n.rede_id'),
            'nucleo' => $contexto->whereColumn('ce.nucleo_id', 'n.id'),
            'escola' => $contexto->whereColumn('ce.escola_id', 'e.id'),
            default => $contexto->whereRaw('1 = 0'),
        };
        return $q->whereExists($contexto);
    }

    public function espacos(): Builder
    {
        if ($this->tipo === 'pessoal') {
            return DB::table('espacos as espaco_permitido')->whereExists($this->contexto()->selectRaw('1')
                ->whereColumn('cv.espaco_id', 'espaco_permitido.id'))->select('espaco_permitido.id');
        }
        return $this->escolas()->select('ee.id');
    }

    public function filtrar($query, string $coluna)
    {
        if (in_array($this->tipo, ['escola', 'pessoal'], true)) return $query->where($coluna, $this->espaco)->whereExists($this->contexto()->selectRaw('1'));
        return $query->whereIn($coluna, $this->espacos());
    }

    public function referencias(string $tipo): Builder
    {
        if ($tipo === $this->tipo && in_array($tipo, ['rede', 'nucleo', 'escola'], true)) {
            return $this->contexto()->select('ce.'.$tipo.'_id');
        }
        if ($tipo === 'turma' && $this->perfil === 'professor') {
            return DB::table('vinculo_turmas as vt')->join('turmas as t', 't.id', '=', 'vt.turma_id')
                ->whereColumn('t.espaco_id', 'vt.espaco_id')->whereExists($this->contexto()->selectRaw('1')
                    ->whereColumn('cv.id', 'vt.vinculo_id')->whereColumn('cv.espaco_id', 'vt.espaco_id')
                    ->whereColumn('ce.escola_id', 't.escola_id'))->select('t.id');
        }
        if ($tipo === 'aluno' && $this->perfil === 'aluno') {
            return DB::table('vinculo_alunos as va')->join('alunos as a', 'a.id', '=', 'va.aluno_id')
                ->join('turmas as t', 't.id', '=', 'a.turma_id')
                ->whereColumn('a.espaco_id', 'va.espaco_id')->whereColumn('t.espaco_id', 'a.espaco_id')
                ->whereExists($this->contexto()->selectRaw('1')->whereColumn('cv.id', 'va.vinculo_id')
                    ->whereColumn('cv.espaco_id', 'va.espaco_id')->whereColumn('ce.escola_id', 't.escola_id'))->select('a.id');
        }
        return DB::table('usuario_vinculos')->whereRaw('1 = 0')->select('id');
    }

    public function turmas(bool $resultadoIndividual = false): Builder
    {
        $q = $this->filtrar(DB::table('turmas'), 'turmas.espaco_id');
        Espacos::conferirEscola($q, 'turmas');
        if ($this->tipo === 'pessoal') return $q->where('turmas.espaco_tipo', 'pessoal')->whereNull('turmas.escola_id');
        if ($this->perfil === 'professor') $q->whereExists(DB::table('vinculo_turmas as vt')->selectRaw('1')
            ->where('vt.vinculo_id', $this->ator->vinculoContextoId())->where('vt.espaco_id', $this->espaco)
            ->whereColumn('vt.turma_id', 'turmas.id')->whereColumn('vt.espaco_id', 'turmas.espaco_id'));
        if ($this->perfil === 'aluno') {
            if (!$resultadoIndividual) return $q->whereRaw('1 = 0');
            $q->whereExists(DB::table('vinculo_alunos as va')->selectRaw('1')->join('alunos as a', 'a.id', '=', 'va.aluno_id')
                ->where('va.vinculo_id', $this->ator->vinculoContextoId())->where('va.espaco_id', $this->espaco)
                ->whereColumn('a.espaco_id', 'va.espaco_id')->whereColumn('a.espaco_id', 'turmas.espaco_id')->whereColumn('a.turma_id', 'turmas.id'));
        }
        return $q;
    }

    public function alunos(): Builder
    {
        if ($this->perfil === 'aluno') {
            return $this->filtrar(DB::table('alunos'), 'alunos.espaco_id')
                ->whereExists(DB::table('vinculo_alunos as va')->selectRaw('1')
                    ->where('va.vinculo_id', $this->ator->vinculoContextoId())->whereColumn('va.aluno_id', 'alunos.id')->whereColumn('va.espaco_id', 'alunos.espaco_id'))
                ->whereExists(Espacos::conferirEscola(DB::table('turmas'), 'turmas')->selectRaw('1')
                    ->whereColumn('turmas.id', 'alunos.turma_id')->whereColumn('turmas.espaco_id', 'alunos.espaco_id'));
        }
        $q = DB::table('alunos')->whereIn('alunos.turma_id', $this->turmas(true)->select('turmas.id'))
            ->whereExists(DB::table('turmas as turma_dona')->selectRaw('1')
                ->whereColumn('turma_dona.id', 'alunos.turma_id')->whereColumn('turma_dona.espaco_id', 'alunos.espaco_id'));
        if ($this->perfil === 'aluno') $q->whereIn('alunos.id', $this->referencias('aluno'));
        return $q;
    }

    public function provas(bool $historico = false, bool $resultadoIndividual = false): Builder
    {
        $q = $this->filtrar(DB::table('provas'), 'provas.espaco_id');
        Espacos::conferirEscola($q, 'provas');
        if ($this->perfil === 'aluno') {
            if (!$resultadoIndividual) return $q->whereRaw('1 = 0');
            return $q->whereExists(DB::table('prova_turmas as pt')->selectRaw('1')
                ->join('turmas as ta', 'ta.id', '=', 'pt.turma_id')->join('alunos as a', 'a.turma_id', '=', 'ta.id')
                ->join('vinculo_alunos as va', 'va.aluno_id', '=', 'a.id')
                ->whereColumn('pt.prova_id', 'provas.id')->whereColumn('pt.espaco_id', 'provas.espaco_id')
                ->whereColumn('ta.espaco_id', 'pt.espaco_id')->whereColumn('ta.escola_id', 'provas.escola_id')
                ->whereColumn('a.espaco_id', 'pt.espaco_id')->whereColumn('va.espaco_id', 'a.espaco_id')
                ->where('va.vinculo_id', $this->ator->vinculoContextoId()));
        }
        if ($this->perfil === 'professor') {
            $q->where(function (Builder $w) use ($historico) {
                $w->where(fn ($propria) => $propria->where('provas.criado_por', $this->ator->id)
                    ->where(fn ($p) => $p->whereNull('provas.perfil_criacao')->orWhere('provas.perfil_criacao', 'professor')))
                    ->orWhere(function (Builder $institucional) use ($historico) {
                        $institucional->whereIn('provas.perfil_criacao', PerfisEscolares::INSTITUCIONAIS)
                            ->whereExists(DB::table('prova_turmas as pt')->selectRaw('1')
                                ->whereColumn('pt.prova_id', 'provas.id')->whereColumn('pt.espaco_id', 'provas.espaco_id')
                                ->whereIn('pt.turma_id', $this->turmas()->select('turmas.id')));
                        if (config('disciplinas.ativo') && $this->tipo === 'escola') {
                            $institucional->whereExists($this->curriculo($historico));
                        }
                    });
            });
        } elseif ($this->perfil === 'professor_autonomo') {
            $q->where('provas.criado_por', $this->ator->id);
        } elseif ($this->perfil === 'aplicador') {
            $q->whereDate('provas.data_aplicacao', RelogioConsulta::hojeUtc())
                ->whereIn('provas.status', ['publicada', 'em_correcao', 'corrigida']);
        }
        return $q;
    }

    private function curriculo(bool $historico): Builder
    {
        return DB::table('prova_disciplinas as pd')->selectRaw('1')
            ->join('disciplinas as d', fn ($j) => $j->on('d.id', '=', 'pd.disciplina_id')->on('d.espaco_id', '=', 'pd.espaco_id'))
            ->join('turma_disciplinas as td', fn ($j) => $j->on('td.disciplina_id', '=', 'pd.disciplina_id')->on('td.espaco_id', '=', 'pd.espaco_id'))
            ->join('prova_turmas as pt', fn ($j) => $j->on('pt.prova_id', '=', 'pd.prova_id')->on('pt.espaco_id', '=', 'pd.espaco_id')->on('pt.turma_id', '=', 'td.turma_id'))
            ->join('turmas as t', fn ($j) => $j->on('t.id', '=', 'td.turma_id')->on('t.espaco_id', '=', 'td.espaco_id'))
            ->whereColumn('pd.prova_id', 'provas.id')->whereColumn('pd.espaco_id', 'provas.espaco_id')
            ->whereIn('td.turma_id', $this->turmas()->select('turmas.id'))->where('d.ativo', true)->where('td.ativo', true)
            ->where(function ($estado) use ($historico) {
                $estado->where('t.ativo', true);
                if ($historico) $estado->orWhere(fn ($h) => $h->where('t.ativo', false)
                    ->whereIn('provas.status', ['publicada', 'em_correcao', 'corrigida'])
                    ->where(fn ($d) => $d->whereDate('provas.data_aplicacao', '<=', RelogioConsulta::hojeSaoPaulo())
                        ->orWhere(fn ($n) => $n->whereNull('provas.data_aplicacao')->whereIn('provas.status', ['em_correcao', 'corrigida']))));
            })
            ->whereExists(DB::table('disciplina_professores as dp')->selectRaw('1')
                ->whereColumn('dp.espaco_id', 'pd.espaco_id')->whereColumn('dp.disciplina_id', 'pd.disciplina_id')
                ->where('dp.vinculo_id', $this->ator->vinculoContextoId())->where('dp.ativo', true))
            ->whereExists(DB::table('docente_atribuicoes as da')->selectRaw('1')->whereColumn('da.espaco_id', 'td.espaco_id')
                ->whereColumn('da.turma_disciplina_id', 'td.id')->where('da.vinculo_id', $this->ator->vinculoContextoId())
                ->where('da.situacao', 'ativa')->where('da.inicio_em', '<=', RelogioConsulta::hojeSaoPaulo())
                ->where(fn ($f) => $f->whereNull('da.fim_em')->orWhere('da.fim_em', '>', RelogioConsulta::hojeSaoPaulo())));
    }

    public function redes(): Builder
    {
        $q = DB::table('redes')->whereIn('redes.id', Instituicoes::redesAtivas()->select('r.id'));
        if ($this->perfil === 'admin_rede') return $q->whereIn('redes.id', $this->referencias('rede'));
        if ($this->perfil === 'dir_nucleo') return $q->whereIn('redes.id', DB::table('nucleos')
            ->whereIn('nucleos.id', $this->referencias('nucleo'))->whereIn('nucleos.id', Instituicoes::nucleosAtivos()->select('n.id'))->select('nucleos.rede_id'));
        return $q->whereIn('redes.id', DB::table('escolas')->join('nucleos', 'nucleos.id', '=', 'escolas.nucleo_id')
            ->whereIn('escolas.id', $this->tabela('escolas')->select('escolas.id'))->select('nucleos.rede_id'));
    }

    public function nucleos(): Builder
    {
        $q = DB::table('nucleos')->whereIn('nucleos.id', Instituicoes::nucleosAtivos()->select('n.id'));
        if ($this->perfil === 'admin_rede') return $q->whereIn('nucleos.rede_id', $this->redes()->select('redes.id'));
        if ($this->perfil === 'dir_nucleo') return $q->whereIn('nucleos.id', $this->referencias('nucleo'));
        return $q->whereRaw('1 = 0');
    }

    public function tabela(string $tabela, bool $historico = false): Builder
    {
        switch ($tabela) {
            case 'turmas': return $this->turmas(true);
            case 'alunos': return $this->alunos();
            case 'provas': return $this->provas($historico, true);
            case 'escolas':
                $q = DB::table('escolas')->whereIn('escolas.id', $this->escolas()->select('e.id'));
                if ($this->perfil === 'aluno') $q->whereIn('escolas.id', DB::table('turmas')
                    ->whereIn('turmas.id', DB::table('alunos')->whereIn('alunos.id', $this->referencias('aluno'))->select('alunos.turma_id'))->select('turmas.escola_id'));
                return $q;
            case 'redes': return $this->redes();
            case 'nucleos': return $this->nucleos();
            case 'prova_turmas':
                return DB::table('prova_turmas')->whereIn('prova_turmas.prova_id', $this->provas($historico, true)->select('provas.id'))
                    ->whereIn('prova_turmas.turma_id', $this->turmas(true)->select('turmas.id'))
                    ->whereExists(DB::table('turmas as tpt')->selectRaw('1')->whereColumn('tpt.id', 'prova_turmas.turma_id')->whereColumn('tpt.espaco_id', 'prova_turmas.espaco_id'))
                    ->whereExists(DB::table('provas as ppt')->selectRaw('1')->whereColumn('ppt.id', 'prova_turmas.prova_id')->whereColumn('ppt.espaco_id', 'prova_turmas.espaco_id'));
            case 'cartoes':
                return DB::table('cartoes')->whereIn('cartoes.prova_id', $this->provas($historico, true)->select('provas.id'))
                    ->whereExists(DB::table('provas as cp')->selectRaw('1')->whereColumn('cp.id', 'cartoes.prova_id')->whereColumn('cp.espaco_id', 'cartoes.espaco_id'))
                    ->where(fn ($a) => $a->whereNull('cartoes.aluno_id')->orWhereExists(DB::table('alunos as ca')->selectRaw('1')
                        ->join('prova_turmas as ct', fn ($j) => $j->whereRaw('ct.turma_id = COALESCE(cartoes.turma_aplicacao_id, ca.turma_id)'))
                        ->whereColumn('ca.id', 'cartoes.aluno_id')->whereColumn('ca.espaco_id', 'cartoes.espaco_id')
                        ->whereColumn('ct.espaco_id', 'cartoes.espaco_id')->whereColumn('ct.prova_id', 'cartoes.prova_id')
                        ->where(function ($acesso) {
                            $acesso->whereIn('ca.id', $this->alunos()->select('alunos.id'));
                            if ($this->perfil === 'professor') $acesso->orWhereIn('cartoes.turma_aplicacao_id', $this->turmas()->select('turmas.id'));
                        })));
            case 'notas':
                return DB::table('notas')->whereIn('notas.cartao_id', $this->tabela('cartoes', $historico)->select('cartoes.id'))
                    ->whereExists(DB::table('cartoes as nc')->selectRaw('1')->join('alunos as na', 'na.id', '=', 'nc.aluno_id')
                        ->whereColumn('nc.id', 'notas.cartao_id')->whereColumn('nc.espaco_id', 'notas.espaco_id')
                        ->whereColumn('nc.prova_id', 'notas.prova_id')->whereColumn('nc.aluno_id', 'notas.aluno_id')
                        ->whereRaw('notas.turma_id = COALESCE(nc.turma_aplicacao_id, na.turma_id)')->whereColumn('na.espaco_id', 'notas.espaco_id'));
            case 'gabaritos':
                return DB::table('gabaritos')->whereIn('gabaritos.prova_id', $this->provas($historico, true)->select('provas.id'))
                    ->whereExists(DB::table('provas as gp')->selectRaw('1')->whereColumn('gp.id', 'gabaritos.prova_id')->whereColumn('gp.espaco_id', 'gabaritos.espaco_id'));
            case 'gabarito_questoes':
                return DB::table('gabarito_questoes')->whereIn('gabarito_id', $this->tabela('gabaritos', $historico)->select('gabaritos.id'))
                    ->whereExists(DB::table('gabaritos as gq')->selectRaw('1')->whereColumn('gq.id', 'gabarito_questoes.gabarito_id')->whereColumn('gq.espaco_id', 'gabarito_questoes.espaco_id'));
            case 'cartao_respostas':
                return DB::table('cartao_respostas')->whereIn('cartao_id', $this->tabela('cartoes', $historico)->select('cartoes.id'))
                    ->whereExists(DB::table('cartoes as rc')->selectRaw('1')->whereColumn('rc.id', 'cartao_respostas.cartao_id')->whereColumn('rc.espaco_id', 'cartao_respostas.espaco_id'));
            case 'visitas':
                return Espacos::conferirEscola(DB::table('visitas'), 'visitas')->whereIn('visitas.espaco_id', $this->espacos())
                    ->whereIn('visitas.escola_id', $this->tabela('escolas')->select('escolas.id'))
                    ->whereExists(DB::table('escolas as ve')->selectRaw('1')->whereColumn('ve.id', 'visitas.escola_id')->whereColumn('ve.nucleo_id', 'visitas.nucleo_id'));
            case 'sincronizacoes_seges':
                return DB::table('sincronizacoes_seges')->where(fn ($d) => $d->whereIn('espaco_id', $this->espacos())
                    ->orWhereIn('espaco_id', Instituicoes::nucleosAtivos()->whereIn('n.id', $this->nucleos()->select('nucleos.id'))->select('en.id'))
                    ->orWhereIn('espaco_id', Instituicoes::redesAtivas()->whereIn('r.id', $this->redes()->select('redes.id'))->select('er.id')));
            case 'usuarios':
                // Gestão inclui convidados/suspensos conforme a fachada anterior; vigência é filtro do consumidor.
                return DB::table('usuarios')->whereIn('usuarios.id', $this->gestao(false)->select('v.usuario_id'));
            default: throw new \InvalidArgumentException('Consulta pedagógica não classificada.');
        }
    }

    public function gestao(bool $vigentes): Builder
    {
        $q = DB::table('usuario_vinculos as v')->join('espacos as ev', 'ev.id', '=', 'v.espaco_id')
            ->join('usuarios as uv', 'uv.id', '=', 'v.usuario_id');
        PredicadosVinculo::espaco($q, 'ev');
        $q->where(function ($ramos) {
            $ramos->where(fn ($e) => $e->where('ev.tipo', 'escola')->whereIn('ev.escola_id', $this->escolas()->select('e.id'))->whereIn('v.perfil', Cadastro::DE_ESCOLA));
            if (in_array($this->perfil, ['admin_rede', 'dir_nucleo'], true)) $ramos->orWhere(fn ($n) => $n->where('ev.tipo', 'nucleo')->whereIn('ev.nucleo_id', $this->nucleos()->select('nucleos.id'))->where('v.perfil', 'dir_nucleo'));
            if ($this->perfil === 'admin_rede') $ramos->orWhere(fn ($r) => $r->where('ev.tipo', 'rede')->whereIn('ev.rede_id', $this->redes()->select('redes.id'))->where('v.perfil', 'admin_rede'));
        });
        if ($vigentes) $q->where('uv.ativo', true)->where('v.situacao', 'ativo')->where('v.inicio_em', '<=', RelogioConsulta::agora())
            ->where(fn ($f) => $f->whereNull('v.fim_em')->orWhere('v.fim_em', '>', RelogioConsulta::agora()));
        return $q;
    }
}
