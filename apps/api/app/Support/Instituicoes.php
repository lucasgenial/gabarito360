<?php

namespace App\Support;

use App\Models\Usuario;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/** Contexto institucional transitório; nunca deriva acesso do cliente pagador. */
class Instituicoes
{
    public static function referencias(Usuario $u, string $tipo): array
    {
        if (ContextoVinculo::presente($u)) return ContextoVinculo::referencias($u, $tipo);
        return DB::table('usuario_escopos')->where('usuario_id', $u->id)->where('escopo_tipo', $tipo)
            ->pluck('escopo_id')->map(fn ($id) => (int) $id)->all();
    }

    public static function redesAtivas()
    {
        return DB::table('espacos as er')->join('redes as r', 'r.id', '=', 'er.rede_id')
            ->where('er.tipo', 'rede')->where('er.situacao', 'ativo')
            ->whereNull('er.nucleo_id')->whereNull('er.escola_id')->whereNull('er.usuario_proprietario_id');
    }

    public static function nucleosAtivos()
    {
        return self::redesAtivas()->join('nucleos as n', 'n.rede_id', '=', 'r.id')
            ->join('espacos as en', 'en.nucleo_id', '=', 'n.id')
            ->where('en.tipo', 'nucleo')->where('en.situacao', 'ativo')->whereColumn('en.cliente_id', 'er.cliente_id')
            ->whereNull('en.rede_id')->whereNull('en.escola_id')->whereNull('en.usuario_proprietario_id');
    }

    public static function redesIds(Usuario $u): ?array
    {
        if (!Espacos::ativo() && $u->perfil === 'admin_rede') return null;
        if (!$u->ativo) return [];
        if ($u->perfil === 'admin_rede') {
            $ids = self::referencias($u, 'rede');
        } elseif ($u->perfil === 'dir_nucleo') {
            $q = DB::table('nucleos')->whereIn('id', self::referencias($u, 'nucleo'));
            if (Espacos::ativo()) $q->whereIn('id', self::nucleosAtivos()->select('n.id'));
            $ids = $q->pluck('rede_id')->all();
        } else {
            $ids = DB::table('escolas')->join('nucleos', 'nucleos.id', '=', 'escolas.nucleo_id')
                ->whereIn('escolas.id', self::escolasLogoIds($u) ?? [])->pluck('nucleos.rede_id')->all();
        }
        $q = DB::table('redes')->whereIn('id', $ids);
        if (Espacos::ativo()) $q->whereIn('id', self::redesAtivas()->select('r.id'));
        return $q->pluck('id')->map(fn ($id) => (int) $id)->all();
    }

    /** O aluno vê somente o cabeçalho da própria escola, sem ampliar Escopo::turmaIds. */
    public static function escolasLogoIds(Usuario $u): ?array
    {
        if ($u->perfil !== 'aluno') return Escopo::escolaIds($u);
        if (!$u->ativo) return [];
        $q = Espacos::agregadoEscolar(DB::table('alunos'), 'alunos')->join('turmas', 'turmas.id', '=', 'alunos.turma_id')
            ->whereIn('alunos.id', self::referencias($u, 'aluno'));
        $ids = $q->pluck('turmas.escola_id')->map(fn ($id) => (int) $id)->all();
        return Espacos::ativo() ? Espacos::escolasPermitidas($u, $ids) : $ids;
    }

    public static function nucleosIds(Usuario $u): ?array
    {
        if (!Espacos::ativo() && in_array($u->perfil, ['admin_rede', 'secretario_educacao'], true)) return null;
        if (!$u->ativo) return [];
        $q = DB::table('nucleos');
        if ($u->perfil === 'admin_rede') $q->whereIn('rede_id', self::redesIds($u) ?? []);
        elseif ($u->perfil === 'dir_nucleo') $q->whereIn('id', self::referencias($u, 'nucleo'));
        else $q->whereRaw('1 = 0');
        if (Espacos::ativo()) $q->whereIn('id', self::nucleosAtivos()->select('n.id'));
        return $q->pluck('id')->map(fn ($id) => (int) $id)->all();
    }

    public static function nucleos(Usuario $u)
    {
        $q = \App\Models\Nucleo::query();
        $ids = self::nucleosIds($u);
        return $ids === null ? $q : $q->whereIn('nucleos.id', $ids);
    }

    public static function selecionarReferencia(Request $request, string $tipo, array $ids): ?int
    {
        $campo = $tipo.'_id';
        $request->validate([$campo => 'sometimes|integer|min:1']);
        $ids = array_values(array_unique(array_map('intval', $ids)));
        if ($request->has($campo)) {
            abort_unless(in_array($request->integer($campo), $ids, true), 404);
            return $request->integer($campo);
        }
        abort_if(Espacos::ativo() && count($ids) > 1, 422, 'Selecione a instituição ou registro para esta consulta.');
        return $ids[0] ?? null;
    }

    /** Sem seleção e com duas redes, recusa ambiguidade em vez de escolher a primeira. */
    public static function redeSelecionada(Request $request): object
    {
        $request->validate(['rede_id' => 'sometimes|integer|min:1']);
        $ids = self::redesIds($request->user());
        $q = DB::table('redes')->when($ids !== null, fn ($q) => $q->whereIn('id', $ids));
        if ($request->has('rede_id')) return $q->where('id', $request->input('rede_id'))->firstOrFail();
        // Compatibilidade da tela atual; remover no corte, junto da propagação do contexto web.
        if (!Espacos::ativo()) return $q->orderBy('id')->firstOrFail();
        $redes = $q->limit(2)->get();
        abort_if($redes->isEmpty(), 404);
        abort_unless($redes->count() === 1, 422, 'Selecione a rede para esta operação.');
        return $redes->sole();
    }

    /** Chamado na mesma transação da instituição; cliente herdado do pai validado. */
    public static function criarEspaco(string $tipo, int $id, string $nome, int $cliente, Usuario $ator): void
    {
        abort_unless(in_array($tipo, ['nucleo', 'escola'], true), 422);
        DB::table('espacos')->insert(['cliente_id' => $cliente, 'tipo' => $tipo, 'nome' => $nome,
            $tipo.'_id' => $id, 'situacao' => 'ativo', 'criado_por' => $ator->id, 'created_at' => now(), 'updated_at' => now()]);
    }
}
