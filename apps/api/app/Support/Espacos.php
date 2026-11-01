<?php

namespace App\Support;

use App\Models\Usuario;
use Illuminate\Support\Facades\DB;

/** Adaptador transitório T2: escopos explícitos legados, sem criar vínculos V1. */
class Espacos
{
    public static function ativo(): bool
    {
        return (bool) config('espacos.ativo', false);
    }

    /** Apenas espaços escolares ativos, com toda a hierarquia ativa e coerente. */
    public static function escolas()
    {
        return DB::table('espacos as ee')
            ->join('escolas as e', 'e.id', '=', 'ee.escola_id')
            ->join('nucleos as n', 'n.id', '=', 'e.nucleo_id')
            ->join('espacos as en', 'en.nucleo_id', '=', 'n.id')
            ->join('espacos as er', 'er.rede_id', '=', 'n.rede_id')
            ->where('ee.tipo', 'escola')->where('en.tipo', 'nucleo')->where('er.tipo', 'rede')
            ->whereNull('ee.rede_id')->whereNull('ee.nucleo_id')->whereNull('ee.usuario_proprietario_id')
            ->whereNull('en.rede_id')->whereNull('en.escola_id')->whereNull('en.usuario_proprietario_id')
            ->whereNull('er.nucleo_id')->whereNull('er.escola_id')->whereNull('er.usuario_proprietario_id')
            ->where('ee.situacao', 'ativo')->where('en.situacao', 'ativo')->where('er.situacao', 'ativo')
            ->whereColumn('ee.cliente_id', 'en.cliente_id')->whereColumn('en.cliente_id', 'er.cliente_id');
    }

    public static function escolasPermitidas(Usuario $u, array $candidatas): array
    {
        RegimeVinculos::exigir($u);
        if (!$u->ativo) return [];
        return self::escolas()->whereIn('e.id', $candidatas)->pluck('e.id')->map(fn ($id) => (int) $id)->all();
    }

    public static function ids(Usuario $u): array
    {
        RegimeVinculos::exigir($u);
        return self::escolas()->whereIn('e.id', Escopo::escolaIds($u) ?? [])
            ->pluck('ee.id')->map(fn ($id) => (int) $id)->all();
    }

    /** Nunca aceita espaco_id vindo do cliente; deriva da escola já autorizada. */
    public static function daEscola(Usuario $u, int $escola): int
    {
        RegimeVinculos::exigir($u);
        abort_unless(in_array($escola, Escopo::escolaIds($u) ?? [], true), 404);
        $id = self::escolas()->where('e.id', $escola)->value('ee.id');
        abort_unless($id, 404);
        return (int) $id;
    }

    public static function autoria(int $espaco, Usuario $u): array
    {
        RegimeVinculos::exigir($u);
        return ['espaco_id' => $espaco, 'criado_por' => $u->id, 'origem_autoria' => 'registrada'];
    }

    public static function filtrar($query, Usuario $u, string $coluna)
    {
        RegimeVinculos::exigir($u);
        if (self::ativo()) $query->whereIn($coluna, self::ids($u));
        return $query;
    }

    public static function conferirEscola($query, string $tabela)
    {
        if (self::ativo()) {
            $query->whereExists(fn ($q) => $q->selectRaw('1')->from('espacos as dono')
                ->whereColumn('dono.id', $tabela.'.espaco_id')->whereColumn('dono.escola_id', $tabela.'.escola_id'));
        }
        return $query;
    }

    /** Integridade dos agregados institucionais; o chamador ainda deve autorizar a escola. */
    public static function agregadoEscolar($query, string $tabela, ?Usuario $ator = null)
    {
        if ($ator) RegimeVinculos::exigir($ator);
        if (!self::ativo()) return $query;
        if ($ator) {
            if ($tabela === 'turmas') $query->whereIn('turmas.id', Escopo::turmas($ator)->select('turmas.id'));
            elseif ($tabela === 'provas') $query->whereIn('provas.id', Escopo::provas($ator)->select('provas.id'));
            else {
                if (in_array($tabela, ['alunos', 'notas', 'prova_turmas'], true)) $query->whereIn($tabela.'.turma_id', Escopo::turmas($ator)->select('turmas.id'));
                if (in_array($tabela, ['notas', 'prova_turmas'], true)) $query->whereIn($tabela.'.prova_id', Escopo::provas($ator)->select('provas.id'));
            }
        }
        $query->whereIn($tabela.'.espaco_id', self::escolas()->select('ee.id'));
        if (in_array($tabela, ['turmas', 'provas'], true)) return self::conferirEscola($query, $tabela);
        $pais = match ($tabela) {
            'alunos' => ['turma_id' => 'turmas'],
            'prova_turmas' => ['turma_id' => 'turmas', 'prova_id' => 'provas'],
            'notas' => ['aluno_id' => 'alunos', 'turma_id' => 'turmas', 'prova_id' => 'provas', 'cartao_id' => 'cartoes'],
            default => throw new \InvalidArgumentException('Agregado não previsto.'),
        };
        foreach ($pais as $fk => $pai) {
            $query->whereExists(fn ($q) => $q->selectRaw('1')->from($pai.' as pai_agregado')
                ->whereColumn('pai_agregado.id', $tabela.'.'.$fk)->whereColumn('pai_agregado.espaco_id', $tabela.'.espaco_id'));
        }
        return $query;
    }
}
