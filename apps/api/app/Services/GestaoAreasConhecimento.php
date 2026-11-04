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
        return $a;
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

    public function salvar(Usuario $u, array $dados, ?int $id = null): object
    {
        return DB::transaction(function () use ($u, $dados, $id) {
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
                $relacao = DB::table('disciplina_areas')->where('espaco_id', $v->espaco_id)->where('area_id', $id)->where('disciplina_id', $d);
                if (in_array($d, $ids, true)) DB::table('disciplina_areas')->insert(['espaco_id' => $v->espaco_id, 'area_id' => $id, 'disciplina_id' => $d]);
                else $relacao->delete();
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
