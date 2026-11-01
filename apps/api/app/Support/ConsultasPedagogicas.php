<?php

namespace App\Support;

use App\Models\Usuario;
use Illuminate\Support\Facades\DB;

/** Consultas de leitura com ator explícito; não guarda contexto entre requisições/jobs. */
class ConsultasPedagogicas
{
    public function __construct(public readonly Usuario $ator) {}

    public function escolasIds(): ?array
    {
        RegimeVinculos::exigir($this->ator);
        if ($this->ator->perfil === 'aluno') return Instituicoes::escolasLogoIds($this->ator);
        if ($this->ator->perfil === 'secretario_educacao' && Espacos::ativo()) {
            // Secretaria não é cliente pagador nem concessão implícita a todas as redes.
            $redes = DB::table('redes')->whereIn('id', Instituicoes::referencias($this->ator, 'rede'))
                ->whereIn('secretaria_id', Instituicoes::referencias($this->ator, 'secretaria'))->pluck('id');
            return Espacos::escolas()->whereIn('n.rede_id', $redes)->pluck('e.id')->all();
        }
        return Escopo::escolaIds($this->ator);
    }

    public function tabela(string $tabela)
    {
        RegimeVinculos::exigir($this->ator);
        $q = DB::table($tabela);
        if (!Espacos::ativo()) return $q;
        if (!$this->ator->ativo) return $q->whereRaw('1 = 0');
        switch ($tabela) {
            case 'escolas':
                return $q->whereIn('escolas.id', $this->escolasIds() ?? []);
            case 'redes':
                if ($this->ator->perfil !== 'secretario_educacao') return $q->whereIn('redes.id', Instituicoes::redesIds($this->ator) ?? []);
                return $q->whereIn('redes.id', Instituicoes::referencias($this->ator, 'rede'))
                    ->whereIn('redes.secretaria_id', Instituicoes::referencias($this->ator, 'secretaria'))
                    ->whereIn('redes.id', Instituicoes::redesAtivas()->select('r.id'));
            case 'nucleos':
                if ($this->ator->perfil === 'secretario_educacao') return $q->whereIn('nucleos.rede_id', $this->tabela('redes')->select('redes.id'))->whereIn('nucleos.id', Instituicoes::nucleosAtivos()->select('n.id'));
                return $q->whereIn('nucleos.id', Instituicoes::nucleosIds($this->ator) ?? []);
            case 'turmas':
                $q = Espacos::agregadoEscolar($q, 'turmas')->whereIn('turmas.escola_id', $this->escolasIds() ?? []);
                if ($this->ator->perfil === 'professor') $q->whereIn('turmas.id', Escopo::turmaIds($this->ator) ?? []);
                if ($this->ator->perfil === 'aluno') $q->whereIn('turmas.id', $this->alunosProprios()->select('alunos.turma_id'));
                return $q;
            case 'alunos':
                return Espacos::agregadoEscolar($q, 'alunos')->whereIn('alunos.turma_id', $this->tabela('turmas')->select('turmas.id'))
                    ->when($this->ator->perfil === 'aluno', fn ($q) => $q->whereIn('alunos.id', Instituicoes::referencias($this->ator, 'aluno')));
            case 'provas':
                if (!in_array($this->ator->perfil, ['aluno', 'secretario_educacao'], true)) return $q->whereIn('provas.id', Escopo::provas($this->ator)->select('provas.id'));
                $q = Espacos::agregadoEscolar($q, 'provas')->whereIn('provas.escola_id', $this->escolasIds() ?? []);
                if ($this->ator->perfil === 'aluno') $q->whereExists(fn ($p) => $p->selectRaw('1')->from('prova_turmas as pt')
                    ->whereColumn('pt.prova_id', 'provas.id')->whereColumn('pt.espaco_id', 'provas.espaco_id')
                    ->whereIn('pt.turma_id', $this->tabela('turmas')->select('turmas.id')));
                return $q;
            case 'prova_turmas':
                return Espacos::agregadoEscolar($q, 'prova_turmas')
                    ->whereIn('prova_turmas.prova_id', $this->tabela('provas')->select('provas.id'))
                    ->whereIn('prova_turmas.turma_id', $this->tabela('turmas')->select('turmas.id'));
            case 'cartoes':
                // Integridade completa antes de contar ou projetar qualquer valor.
                return $q->whereIn('cartoes.prova_id', $this->tabela('provas')->select('provas.id'))
                    ->whereExists(fn ($p) => $p->selectRaw('1')->from('provas as cp')->whereColumn('cp.id', 'cartoes.prova_id')->whereColumn('cp.espaco_id', 'cartoes.espaco_id'))
                    ->where(fn ($a) => $a->whereNull('cartoes.aluno_id')->orWhereExists(fn ($s) => $s->selectRaw('1')->from('alunos as ca')
                        ->join('prova_turmas as ct', 'ct.turma_id', '=', 'ca.turma_id')->whereColumn('ca.id', 'cartoes.aluno_id')
                        ->whereColumn('ca.espaco_id', 'cartoes.espaco_id')->whereColumn('ct.espaco_id', 'cartoes.espaco_id')
                        ->whereColumn('ct.prova_id', 'cartoes.prova_id')->whereIn('ca.id', $this->tabela('alunos')->select('alunos.id'))));
            case 'notas':
                return $q->whereIn('notas.cartao_id', $this->tabela('cartoes')->select('cartoes.id'))
                    ->whereExists(fn ($n) => $n->selectRaw('1')->from('cartoes as nc')->join('alunos as na', 'na.id', '=', 'nc.aluno_id')
                        ->whereColumn('nc.id', 'notas.cartao_id')->whereColumn('nc.espaco_id', 'notas.espaco_id')
                        ->whereColumn('nc.prova_id', 'notas.prova_id')->whereColumn('nc.aluno_id', 'notas.aluno_id')
                        ->whereColumn('na.turma_id', 'notas.turma_id')->whereColumn('na.espaco_id', 'notas.espaco_id'));
            case 'gabaritos':
                return $q->whereIn('gabaritos.prova_id', $this->tabela('provas')->select('provas.id'))
                    ->whereExists(fn ($p) => $p->selectRaw('1')->from('provas as gp')->whereColumn('gp.id', 'gabaritos.prova_id')->whereColumn('gp.espaco_id', 'gabaritos.espaco_id'));
            case 'gabarito_questoes':
                return $q->whereIn('gabarito_id', $this->tabela('gabaritos')->select('gabaritos.id'))
                    ->whereExists(fn ($g) => $g->selectRaw('1')->from('gabaritos as gq')->whereColumn('gq.id', 'gabarito_questoes.gabarito_id')->whereColumn('gq.espaco_id', 'gabarito_questoes.espaco_id'));
            case 'cartao_respostas':
                return $q->whereIn('cartao_id', $this->tabela('cartoes')->select('cartoes.id'))
                    ->whereExists(fn ($c) => $c->selectRaw('1')->from('cartoes as rc')->whereColumn('rc.id', 'cartao_respostas.cartao_id')->whereColumn('rc.espaco_id', 'cartao_respostas.espaco_id'));
            case 'visitas':
                return Espacos::conferirEscola($q, 'visitas')->whereIn('visitas.espaco_id', Espacos::escolas()->select('ee.id'))->whereIn('visitas.escola_id', $this->tabela('escolas')->select('escolas.id'))
                    ->whereExists(fn ($e) => $e->selectRaw('1')->from('escolas as ve')->whereColumn('ve.id', 'visitas.escola_id')->whereColumn('ve.nucleo_id', 'visitas.nucleo_id'));
            case 'sincronizacoes_seges':
                return $q->where(fn ($d) => $d->whereIn('espaco_id', Espacos::escolas()->whereIn('e.id', $this->escolasIds() ?? [])->select('ee.id'))
                    ->orWhereIn('espaco_id', Instituicoes::nucleosAtivos()->whereIn('n.id', Instituicoes::nucleosIds($this->ator) ?? [])->select('en.id'))
                    ->orWhereIn('espaco_id', Instituicoes::redesAtivas()->whereIn('r.id', Instituicoes::redesIds($this->ator) ?? [])->select('er.id')));
            case 'usuarios':
                return $q->whereIn('usuarios.id', GestaoLegada::usuarios($this->ator)->select('usuarios.id'));
            default:
                throw new \InvalidArgumentException('Consulta pedagógica não classificada.');
        }
    }

    private function alunosProprios()
    {
        return Espacos::agregadoEscolar(DB::table('alunos'), 'alunos', $this->ator)->whereIn('alunos.id', Instituicoes::referencias($this->ator, 'aluno'));
    }

    public function modelo(string $classe)
    {
        RegimeVinculos::exigir($this->ator);
        $modelo = new $classe();
        $q = $modelo->newQuery();
        return Espacos::ativo() ? $q->whereIn($modelo->getTable().'.id', $this->tabela($modelo->getTable())->select($modelo->getTable().'.id')) : $q;
    }
}
