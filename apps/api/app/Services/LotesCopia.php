<?php

namespace App\Services;

use App\Models\Usuario;
use App\Support\{ContextoVinculo, ElegibilidadeCopia, RegimeVinculos};
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Symfony\Component\HttpKernel\Exception\HttpException;

/** Fundação interna. Nenhum método publica dados, grava mapas ou despacha jobs. */
class LotesCopia
{
    private const POLITICA = 't5-material-v1';

    public function criar(Usuario $solicitante, int $origem, int $destino, string $chave, array $provas): string
    {
        abort_unless(Str::isUuid($chave) && $origem !== $destino && count($provas) >= 1 && count($provas) <= 100, 422, 'Seleção inválida.');
        abort_unless(count(array_filter($provas, fn ($id) => is_int($id) && $id > 0)) === count($provas)
            && count(array_unique($provas)) === count($provas), 422, 'Seleção inválida.');
        sort($provas); $chave = strtolower($chave);
        return $this->transacao(function () use ($solicitante, $origem, $destino, $chave, $provas) {
            $ator = ElegibilidadeCopia::lado($solicitante, $origem, 'origem');
            $espaco = DB::table('espacos')->find($destino);
            abort_unless($espaco && in_array($espaco->tipo, ['escola', 'pessoal'], true)
                && ContextoVinculo::espacoValido($espaco), 403, 'Cópia indisponível.');
            $pedido = $this->digest([$ator['usuario_id'], $ator['vinculo_id'], $origem, $destino, self::POLITICA, $provas]);
            $existente = DB::table('copias_lotes')->where('chave', $chave)->lockForUpdate()->first();
            if ($existente) {
                abort_unless(hash_equals($existente->pedido_digest, $pedido), 409, 'Chave já utilizada.');
                // A repetição não renova consentimentos nem fotografa novamente a fonte.
                return $existente->id;
            }
            abort_if(DB::table('registros_origem')->where('origem_espaco_id', $origem)->where('destino_espaco_id', $destino)
                ->where('entidade', 'prova')->whereIn('origem_id', $provas)->exists(), 409, 'Seleção já possui cópia.');
            $itens = [];
            foreach ($provas as $id) $itens[] = ['entidade'=>'prova', 'origem_id'=>$id, 'fonte_digest'=>$this->fonte($origem, $id)];
            $selecao = $this->digest([$origem, $destino, 'preparacao_material', self::POLITICA, $itens]);
            $lote = (string) Str::uuid();
            DB::table('copias_lotes')->insert(['id'=>$lote, 'chave'=>$chave, 'origem_espaco_id'=>$origem,
                'destino_espaco_id'=>$destino, 'solicitante_id'=>$ator['usuario_id'], 'solicitante_vinculo_id'=>$ator['vinculo_id'],
                'finalidade'=>'preparacao_material', 'politica'=>self::POLITICA, 'pedido_digest'=>$pedido,
                'selecao_digest'=>$selecao, 'estado'=>'aguardando_autorizacao', 'total_itens'=>count($itens), 'auditoria_versao'=>1,
                'created_at'=>now(), 'updated_at'=>now()]);
            foreach ($itens as $item) DB::table('copias_itens')->insert($item + ['lote_id'=>$lote,
                'origem_espaco_id'=>$origem, 'destino_espaco_id'=>$destino]);
            OutboxCopia::registrar($lote,$solicitante,'solicitado');
            return $lote;
        });
    }

    public function aprovar(Usuario $ator, string $id, string $lado, CarbonImmutable $validaAte): array
    {
        return $this->transacao(function () use ($ator, $id, $lado, $validaAte) {
            $lote = $this->lote($id);
            $contexto = $this->contexto($ator, $lote, $lado);
            abort_unless($lote->estado !== 'bloqueado' && $validaAte->isFuture(), 409, 'Decisão indisponível.');
            abort_unless($this->fonteAtual($lote), 409, 'Seleção mudou; prepare novo lote.');
            $validade = $validaAte->utc()->format('Y-m-d H:i:s');
            // Precisão persistida é de segundos; não admitir validade truncada já vencida.
            abort_unless(CarbonImmutable::parse($validade, 'UTC')->isFuture(), 422, 'Validade inválida.');
            $anterior = DB::table('copias_autorizacoes')->where('lote_id', $id)->where('lado', $lado)->first();
            if ($anterior) {
                abort_unless((int) $anterior->ator_id === $contexto['usuario_id']
                    && (int) $anterior->vinculo_id === $contexto['vinculo_id']
                    && $anterior->versao_acesso === $contexto['versao_acesso']
                    && $anterior->valida_ate === $validade && !$anterior->revogada_em, 409, 'Decisão já registrada.');
                $decisao = $anterior->id;
            } else {
                $decisao = (string) Str::uuid();
                DB::table('copias_autorizacoes')->insert(['id'=>$decisao, 'lote_id'=>$id, 'lado'=>$lado,
                    'origem_espaco_id'=>$lote->origem_espaco_id, 'destino_espaco_id'=>$lote->destino_espaco_id,
                    'ator_id'=>$contexto['usuario_id'], 'vinculo_id'=>$contexto['vinculo_id'],
                    'versao_acesso'=>$contexto['versao_acesso'], 'selecao_digest'=>$lote->selecao_digest,
                    'valida_ate'=>$validade, 'created_at'=>now()]);
                OutboxCopia::registrar($id,$ator,'aprovado',$decisao);
            }
            $this->conferir($lote,$ator);
            return ['decisao_id'=>$decisao, 'estado'=>DB::table('copias_lotes')->where('id', $id)->value('estado')];
        });
    }

    /** Retorna elegibilidade atual, nunca permissão de execução; A3 ainda deve confirmar auditoria. */
    public function revalidar(Usuario $ator, string $id, string $lado): bool
    {
        return $this->transacao(function () use ($ator, $id, $lado) {
            $lote = $this->lote($id);
            $this->contexto($ator, $lote, $lado);
            return $this->conferir($lote,$ator);
        });
    }

    public function revogar(Usuario $ator, string $id, string $lado): void
    {
        $this->transacao(function () use ($ator, $id, $lado) {
            $lote = $this->lote($id);
            $contexto = $this->contexto($ator, $lote, $lado);
            // Gestor atualmente elegível do mesmo lado pode interromper, sem aprovar em nome do anterior.
            $decisao = DB::table('copias_autorizacoes')->where('lote_id',$id)->where('lado',$lado)->first();
            $alteradas = DB::table('copias_autorizacoes')->where('lote_id', $id)->where('lado', $lado)->whereNull('revogada_em')
                ->update(['revogada_em'=>now(), 'revogada_por'=>$contexto['usuario_id'],
                    'revogada_vinculo_id'=>$contexto['vinculo_id'], 'revogada_versao_acesso'=>$contexto['versao_acesso']]);
            if ($alteradas) OutboxCopia::registrar($id,$ator,'revogado',$decisao->id);
            $this->estado($lote, 'bloqueado',$ator);
        });
    }

    private function conferir(object $lote, Usuario $ator): bool
    {
        if ($lote->estado === 'bloqueado') return false;
        $decisoes = DB::table('copias_autorizacoes')->where('lote_id', $lote->id)->get();
        try {
            if (!$this->fonteAtual($lote)) { $this->estado($lote, 'bloqueado',$ator); return false; }
            foreach ($decisoes as $d) {
                if ($d->revogada_em || CarbonImmutable::parse($d->valida_ate, 'UTC')->lessThanOrEqualTo(now())
                    || $d->selecao_digest !== $lote->selecao_digest) {
                    $this->estado($lote, 'bloqueado',$ator); return false;
                }
                $usuario = Usuario::find($d->ator_id);
                abort_unless($usuario, 403);
                $contexto = $this->contexto($usuario->noVinculo($d->vinculo_id), $lote, $d->lado);
                if (!hash_equals($d->versao_acesso, $contexto['versao_acesso'])) {
                    $this->estado($lote, 'bloqueado',$ator); return false;
                }
            }
        } catch (HttpException $e) {
            if (!in_array($e->getStatusCode(), [403, 404], true)) throw $e;
            $this->estado($lote, 'bloqueado',$ator); return false;
        }
        if ($decisoes->count() !== 2) return false;
        $this->estado($lote, 'aguardando_auditoria',$ator);
        return true;
    }

    private function contexto(Usuario $ator, object $lote, string $lado): array
    {
        abort_unless(in_array($lado, ['origem', 'destino'], true), 422, 'Lado inválido.');
        return ElegibilidadeCopia::lado($ator, (int) $lote->{$lado.'_espaco_id'}, $lado);
    }

    private function lote(string $id): object
    {
        $lote = DB::table('copias_lotes')->where('id', $id)->lockForUpdate()->first();
        abort_unless($lote && $lote->politica === self::POLITICA && (int)$lote->auditoria_versao === 1, 404, 'Lote indisponível; prepare nova seleção.');
        return $lote;
    }

    private function estado(object $lote, string $estado, Usuario $ator): void
    {
        if ($lote->estado === $estado) return;
        DB::table('copias_lotes')->where('id', $lote->id)->update(['estado'=>$estado, 'updated_at'=>now()]);
        if ($estado === 'bloqueado') OutboxCopia::registrar($lote->id,$ator,'bloqueado');
    }

    private function fonteAtual(object $lote): bool
    {
        $itens = DB::table('copias_itens')->where('lote_id', $lote->id)->orderBy('origem_id')->get();
        if ($itens->count() !== (int) $lote->total_itens) return false;
        if (DB::table('registros_origem')->where('origem_espaco_id',$lote->origem_espaco_id)
            ->where('destino_espaco_id',$lote->destino_espaco_id)->where('entidade','prova')
            ->whereIn('origem_id',$itens->pluck('origem_id'))->where('lote_id','<>',$lote->id)->exists()) return false;
        $selecao = [];
        foreach ($itens as $item) {
            if ($item->entidade !== 'prova' || !hash_equals($item->fonte_digest, $this->fonte((int) $lote->origem_espaco_id, (int) $item->origem_id))) return false;
            $selecao[] = ['entidade'=>'prova', 'origem_id'=>(int) $item->origem_id, 'fonte_digest'=>$item->fonte_digest];
        }
        return hash_equals($lote->selecao_digest, $this->digest([(int) $lote->origem_espaco_id,
            (int) $lote->destino_espaco_id, $lote->finalidade, $lote->politica, $selecao]));
    }

    private function fonte(int $espaco, int $id): string
    {
        $prova = DB::table('provas')->where('espaco_id', $espaco)->where('id', $id)->first();
        abort_unless($prova, 404, 'Seleção indisponível.');
        $gabarito = DB::table('gabaritos')->where('prova_id', $id)->first();
        abort_if($gabarito && (int) $gabarito->espaco_id !== $espaco, 403, 'Seleção indisponível.');
        $questoes = $gabarito ? DB::table('gabarito_questoes')->where('gabarito_id', $gabarito->id)->orderBy('id')->get()->all() : [];
        foreach ($questoes as $questao) abort_if((int) $questao->espaco_id !== $espaco, 403, 'Seleção indisponível.');
        // Não persiste conteúdo; hash conservador inclui alterações de quaisquer campos da fonte.
        return $this->digest([$prova, $gabarito, $questoes]);
    }

    private function digest(array $dados): string
    {
        return hash('sha256', json_encode($dados, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
    }

    private function transacao(callable $acao): mixed
    {
        abort_unless(config('copias.ativo') === true && RegimeVinculos::obrigatorio(), 403, 'Cópia indisponível.');
        if (DB::transactionLevel() !== 0) throw new \LogicException('T5 exige transação própria para revalidar autorizações.');
        $driver = DB::connection()->getDriverName();
        if (in_array($driver, ['mysql', 'mariadb'], true)) DB::statement('SET TRANSACTION ISOLATION LEVEL SERIALIZABLE');
        elseif ($driver !== 'sqlite' || !app()->environment('testing')) throw new \LogicException('T5 exige MariaDB.');
        return DB::transaction($acao);
    }
}
