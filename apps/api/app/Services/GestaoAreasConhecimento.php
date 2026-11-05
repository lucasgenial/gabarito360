<?php

namespace App\Services;

use App\Models\Usuario;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

final class GestaoAreasConhecimento
{
    public function __construct(private GestaoDisciplinas $disciplinas) {}

    public function consultar(Usuario $u): Builder
    {
        $v = $this->disciplinas->contexto($u);
        return DB::table('areas_conhecimento')->where('espaco_id', $v->espaco_id);
    }

    public function area(Usuario $u, int $id): object
    {
        $a = $this->consultar($u)->where('id', $id)->first();
        abort_unless($a, 404, 'Área não encontrada.');
        $a->disciplinas = $this->disciplinasDaArea($u, $id);
        $a->vinculos = $this->historico($u, $id);
        return $a;
    }

    public function historico(Usuario $u, int $area): array
    {
        $v = $this->disciplinas->contexto($u);
        return DB::table('disciplina_area_vinculos as p')->join('disciplinas as d', function ($j) {
            $j->on('d.id','=','p.disciplina_id')->on('d.espaco_id','=','p.espaco_id');
        })->where('p.espaco_id',$v->espaco_id)->where('p.area_id',$area)
            ->whereIn('d.id',$this->disciplinas->disciplinas($u)->select('d.id'))
            ->orderByDesc('p.ativo')->orderBy('d.nome')->orderByDesc('p.id')
            ->get(['p.id','p.disciplina_id','p.ativo','p.versao','p.vinculado_em','p.desvinculado_em','d.nome','d.sigla','d.ativo as disciplina_ativa','d.versao as disciplina_versao'])->all();
    }

    public function alterarVinculo(Usuario $u, int $area, array $dados, bool $encerrar = false): object
    {
        return DB::transaction(function () use ($u,$area,$dados,$encerrar) {
            DB::table('usuario_vinculos')->where('id',$u->vinculoContextoId())->lockForUpdate()->first();
            $v = $this->disciplinas->contexto($u,true);
            DB::table('espacos')->where('id',$v->espaco_id)->lockForUpdate()->first();
            $a = $this->area($u,$area);
            abort_unless((int)$a->versao === (int)$dados['versao'],409,'A área mudou. Atualize a tabela antes de continuar.');
            $d = $this->disciplinas->disciplina($u,(int)$dados['disciplina_id']);
            abort_unless((int)$d->versao === (int)$dados['disciplina_versao'],409,'A disciplina mudou. Atualize a tabela antes de continuar.');
            $ids = array_map(fn($d)=>(int)$d->id,$a->disciplinas);
            if ($encerrar) {
                $p = DB::table('disciplina_areas')->where(['espaco_id'=>$v->espaco_id,'area_id'=>$area,'disciplina_id'=>$d->id])->first();
                abort_unless($p && (int)$p->vinculo_id === (int)$dados['vinculo_id'],409,'O vínculo mudou. Atualize a tabela.');
                $ids = array_values(array_diff($ids,[(int)$d->id]));
            } else {
                abort_if(in_array((int)$d->id,$ids,true),409,'A disciplina já está vinculada.');
                $ids[] = (int)$d->id;
            }
            return $this->salvar($u,['nome'=>$a->nome,'sigla'=>$a->sigla,'ativo'=>(bool)$a->ativo,'versao'=>$a->versao,'disciplina_ids'=>$ids],$area,$dados['confirmacao']??null);
        },3);
    }

    public function disciplinasDaArea(Usuario $u, int $id): array
    {
        $v = $this->disciplinas->contexto($u);
        return $this->disciplinas->disciplinas($u)->whereExists(fn ($q) => $q->selectRaw('1')->from('disciplina_areas as da')
            ->whereColumn('da.disciplina_id', 'd.id')->where('da.espaco_id', $v->espaco_id)->where('da.area_id', $id))
            ->orderBy('d.nome')->orderBy('d.id')->get(['d.id', 'd.nome', 'd.sigla', 'd.ativo', 'd.versao'])->all();
    }

    public function opcoes(Usuario $u, ?int $area = null, array $rascunho = []): array
    {
        $v = $this->disciplinas->contexto($u, true);
        if ($area) $this->area($u, $area);
        return $this->disciplinas->disciplinas($u)->where(function ($q) use ($area, $v, $rascunho) {
            $q->where('d.ativo', true);
            if ($area) $q->orWhereIn('d.id', DB::table('disciplina_areas')->where('espaco_id', $v->espaco_id)->where('area_id', $area)->select('disciplina_id'));
            if ($rascunho) $q->orWhereIn('d.id', $rascunho);
        })->orderBy('d.nome')->orderBy('d.id')->get(['d.id', 'd.nome', 'd.sigla', 'd.ativo', 'd.versao'])->all();
    }

    public function salvar(Usuario $u, array $dados, ?int $id = null, ?string $confirmacao = null): object
    {
        return DB::transaction(function () use ($u, $dados, $id, $confirmacao) {
            DB::table('usuario_vinculos')->where('id', $u->vinculoContextoId())->lockForUpdate()->first();
            $v = $this->disciplinas->contexto($u, true);
            // Mesma trava do catálogo de disciplinas: serializa nome e associação/inativação.
            DB::table('espacos')->where('id', $v->espaco_id)->lockForUpdate()->first();
            $antes = $id ? $this->consultar($u)->where('id', $id)->lockForUpdate()->first() : null;
            if ($id) abort_unless($antes, 404, 'Área não encontrada.');
            if ($antes) abort_unless((int) $antes->versao === (int) $dados['versao'], 409, 'Os dados mudaram. Atualize a página antes de salvar novamente.');
            $disciplinasAntes = $antes ? $this->disciplinasDaArea($u, $id) : [];
            $idsAntes = array_map(fn ($d) => (int) $d->id, $disciplinasAntes);
            $ids = array_key_exists('disciplina_ids', $dados) ? array_map('intval', $dados['disciplina_ids']) : null;
            $alteradas = [];
            if ($ids !== null) {
                sort($ids);
                $disciplinas = DB::table('disciplinas')->where('espaco_id', $v->espaco_id)->whereIn('id', array_unique([...$ids, ...$idsAntes]))->orderBy('id')->lockForUpdate()->get()->keyBy('id');
                if (count(array_intersect($ids, $disciplinas->keys()->all())) !== count($ids)) throw ValidationException::withMessages(['disciplina_ids' => 'Escolha somente disciplinas disponíveis nesta escola.']);
                $novas = array_diff($ids, $idsAntes);
                if ($novas && (!$dados['ativo'] || collect($novas)->contains(fn ($d) => !$disciplinas[$d]->ativo))) {
                    throw ValidationException::withMessages(['disciplina_ids' => 'Novas associações exigem área e disciplinas ativas.']);
                }
                $alteradas = array_values(array_unique([...array_diff($ids, $idsAntes), ...array_diff($idsAntes, $ids)]));
                // Confere nomes/situações/associações que mudaram desde a seleção, sem sobrescrever.
                if (isset($dados['disciplina_versoes'])) foreach (array_unique([...$ids, ...$alteradas]) as $d) {
                    abort_unless(isset($dados['disciplina_versoes'][$d]) && (int) $dados['disciplina_versoes'][$d] === (int) $disciplinas[$d]->versao, 409, 'Uma disciplina mudou. Atualize o estado e confira sua seleção antes de salvar.');
                }
            }
            $nome = preg_replace('/\s+/u', ' ', trim($dados['nome']));
            $chave = Str::lower(Str::ascii($nome));
            if ($nome === '' || mb_strlen($chave) > 100) throw ValidationException::withMessages(['nome' => 'Informe um nome de área válido e mais curto.']);
            if ($this->consultar($u)->where('nome_chave', $chave)->when($id, fn ($q) => $q->where('id', '!=', $id))->exists()) {
                throw ValidationException::withMessages(['nome' => 'Esta área já está no catálogo da escola.']);
            }
            $valores = ['nome' => $nome, 'nome_chave' => $chave, 'sigla' => trim($dados['sigla'] ?? '') ?: null,
                'ativo' => (bool) $dados['ativo'], 'versao' => ($antes?->versao ?? 0) + 1, 'updated_at' => now()];
            if ($antes) DB::table('areas_conhecimento')->where('id', $id)->update($valores);
            else $id = DB::table('areas_conhecimento')->insertGetId($valores + ['espaco_id' => $v->espaco_id, 'created_at' => now()]);
            foreach ($alteradas as $d) {
                $anterior = clone $disciplinas[$d];
                $anterior->areas = $this->disciplinas->areasDaDisciplina((int) $v->espaco_id, (int) $d);
                if (in_array($d, $ids, true)) PeriodosAreaDisciplina::abrir((int)$v->espaco_id,$id,(int)$d);
                else PeriodosAreaDisciplina::encerrar((int)$v->espaco_id,$id,(int)$d,(int)$v->id,$confirmacao);
                DB::table('disciplinas')->where('id', $d)->update(['versao' => $anterior->versao + 1, 'updated_at' => now()]);
                $atual = DB::table('disciplinas')->find($d);
                $atual->areas = $this->disciplinas->areasDaDisciplina((int) $v->espaco_id, (int) $d);
                $this->disciplinas->evento($v, 'disciplina', $d, 'areas_alteradas', $anterior, $atual);
            }
            if ($antes) $antes->disciplinas = $disciplinasAntes;
            $depois = $this->area($u, $id);
            $this->disciplinas->evento($v, 'area_conhecimento', $id, $antes ? 'atualizada' : 'criada', $antes, $depois);
            return $depois;
        }, 3);
    }
}
