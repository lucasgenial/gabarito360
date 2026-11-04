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
        return $a;
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
            $depois = $this->area($u, $id);
            $this->disciplinas->evento($v, 'area_conhecimento', $id, $antes ? 'atualizada' : 'criada', $antes, $depois);
            return $depois;
        }, 3);
    }
}
