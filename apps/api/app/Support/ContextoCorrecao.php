<?php

namespace App\Support;

use App\Models\Cartao;
use App\Models\Prova;
use App\Models\Usuario;
use Illuminate\Support\Facades\DB;

/** Contexto explícito dos consumidores OMR. Filtro de integridade não substitui permissão. */
class ContextoCorrecao
{
    public static function filtrar($query, ?int $espaco, string $coluna = 'espaco_id')
    {
        if (Espacos::ativo()) $query->where($coluna, $espaco ?: 0);
        return $query;
    }

    public static function exigirFilhos(string $tabela, string $coluna, int $id, ?int $espaco): void
    {
        if (!Espacos::ativo()) return;
        abort_if(DB::table($tabela)->where($coluna, $id)->where(fn ($q) => $q->whereNull('espaco_id')->orWhere('espaco_id', '<>', $espaco ?: 0))->exists(), 409, 'Dados da correção precisam de conciliação.');
    }

    public static function autoria(?int $espaco, ?int $ator): array
    {
        if (!Espacos::ativo()) return [];
        abort_unless($espaco && $espaco > 0, 409, 'Contexto de correção indisponível.');
        return ['espaco_id' => $espaco, 'criado_por' => $ator, 'origem_autoria' => $ator ? 'registrada' : 'sistema'];
    }

    public static function provaValida(?Prova $prova): bool
    {
        if (!$prova) return false;
        if (Espacos::ativo() && $prova->escola_id === null) {
            $e = DB::table('espacos')->where('id', $prova->espaco_id)->where('tipo', 'pessoal')->first();
            return config('espacos.pessoal', false) && $e && ContextoVinculo::espacoValido($e);
        }
        return !Espacos::ativo() || Espacos::escolas()->where('ee.id', $prova->espaco_id ?: 0)->where('e.id', $prova->escola_id)->exists();
    }

    public static function alunos(Prova $prova, ?Usuario $ator = null)
    {
        $q = DB::table('alunos')->join('turmas', 'turmas.id', '=', 'alunos.turma_id')
            ->join('prova_turmas', 'prova_turmas.turma_id', '=', 'alunos.turma_id')->where('prova_turmas.prova_id', $prova->id);
        if (Espacos::ativo()) {
            $q->where('alunos.espaco_id', $prova->espaco_id ?: 0)->whereColumn('turmas.espaco_id', 'alunos.espaco_id')
                ->whereColumn('prova_turmas.espaco_id', 'alunos.espaco_id')->where('turmas.escola_id', $prova->escola_id);
            if (!self::provaValida($prova)) $q->whereRaw('1 = 0');
            if ($ator) $q->whereIn('alunos.turma_id', Escopo::turmaIds($ator) ?? []);
        }
        return $q;
    }

    public static function cartaoValido(?Cartao $cartao, ?Prova $prova = null): bool
    {
        if (!$cartao) return false;
        if (!Espacos::ativo()) return true;
        $prova ??= Prova::find($cartao->prova_id);
        return self::provaValida($prova) && (int) $cartao->prova_id === (int) $prova->id
            && (int) $cartao->espaco_id === (int) $prova->espaco_id
            && (!$cartao->aluno_id || DB::table('alunos')->where('id', $cartao->aluno_id)->where('espaco_id', $cartao->espaco_id)
                ->whereExists(fn ($q) => $q->selectRaw('1')->from('prova_turmas')->where('prova_id', $prova->id)->where('espaco_id', $cartao->espaco_id)
                    ->whereRaw('prova_turmas.turma_id = COALESCE(?, alunos.turma_id)', [$cartao->turma_aplicacao_id]))->exists());
    }

    public static function cartoes(Usuario $ator)
    {
        $q = Cartao::query()->whereIn('prova_id', Escopo::provas($ator)->select('provas.id'));
        if (Espacos::ativo()) {
            $q->whereIn('cartoes.espaco_id', Espacos::ids($ator))
                ->whereExists(fn ($p) => $p->selectRaw('1')->from('provas as dona')
                    ->whereColumn('dona.id', 'cartoes.prova_id')->whereColumn('dona.espaco_id', 'cartoes.espaco_id'))
                ->where(fn ($a) => $a->whereNull('cartoes.aluno_id')->orWhereIn('cartoes.aluno_id', Escopo::alunos($ator)->select('alunos.id')));
            $q->where(fn ($a) => $a->whereNull('cartoes.aluno_id')->orWhereExists(fn ($s) => $s->selectRaw('1')->from('alunos as vinculado')
                ->join('prova_turmas as pt', fn ($j) => $j->whereRaw('pt.turma_id = COALESCE(cartoes.turma_aplicacao_id, vinculado.turma_id)'))
                ->whereColumn('vinculado.id', 'cartoes.aluno_id')->whereColumn('vinculado.espaco_id', 'cartoes.espaco_id')
                ->whereColumn('pt.espaco_id', 'cartoes.espaco_id')->whereColumn('pt.prova_id', 'cartoes.prova_id')));
        }
        return $q;
    }

    public static function versaoAcesso(Usuario $ator): string
    {
        if (ContextoVinculo::presente($ator)) return ContextoVinculo::versaoAcesso($ator);
        Permissao::limparCache();
        $escopos = DB::table('usuario_escopos')->where('usuario_id', $ator->id)->orderBy('id')->get()->toArray();
        $permissoes = Permissao::efetivas($ator); sort($permissoes);
        return hash('sha256', json_encode([$ator->id, $ator->perfil, $ator->ativo, (string) $ator->updated_at, $escopos, $permissoes], JSON_THROW_ON_ERROR));
    }
}
