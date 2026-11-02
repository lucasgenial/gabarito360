<?php

namespace App\Services;

use App\Jobs\{EntregarAuditoriaCopia, GravarAuditoria};
use App\Models\Usuario;
use App\Support\EventoCopia;
use Illuminate\Contracts\Bus\Dispatcher;
use Illuminate\Support\Facades\{DB, Log};

final class OutboxCopia
{
    public static function registrar(string $loteId, Usuario $ator, string $acao, ?string $decisao = null): void
    {
        if (DB::transactionLevel() === 0) throw new \LogicException('Recibo exige a transação de negócio.');
        $lote = DB::table('copias_lotes')->find($loteId);
        if (!$lote || (int)$lote->auditoria_versao !== 1) throw new \LogicException('Lote anterior ao contrato de auditoria.');
        $evento = EventoCopia::criar($lote,$ator,$acao,$decisao);
        DB::table('copias_outbox')->insert(['id'=>$evento['id'],'lote_id'=>$loteId,
            'evento'=>json_encode($evento,JSON_THROW_ON_ERROR), 'digest'=>self::digest($evento),
            'proxima_tentativa_em'=>now(),'created_at'=>now()]);
    }

    public static function digest(array $evento): string
    {
        return hash('sha256',ConferenciaAuditoria::linhaCanonica((object)$evento));
    }

    public function publicar(int $limite = 100): array
    {
        if ($limite < 1 || $limite > 100) throw new \InvalidArgumentException('Limite entre 1 e 100.');
        $contagem = ['enviados'=>0,'falhas'=>0];
        if (!config('copias.auditoria_entrega')) return $contagem;
        $conexao = config('auditoria.connection');
        if (config("queue.connections.$conexao.driver") !== 'redis') throw new \LogicException('Outbox exige Redis.');
        $ids = DB::table('copias_outbox')->whereNull('entregue_em')->where('proxima_tentativa_em','<=',now())
            ->orderBy('proxima_tentativa_em')->orderBy('id')->limit($limite)->pluck('id');
        foreach ($ids as $id) {
            DB::transaction(function () use ($id,$conexao,&$contagem) {
                $r = DB::table('copias_outbox')->where('id',$id)->lockForUpdate()->first();
                if (!$r || $r->entregue_em || $r->proxima_tentativa_em > now()) return;
                try {
                    app(Dispatcher::class)->dispatch((new EntregarAuditoriaCopia($id))->onConnection($conexao));
                    DB::table('copias_outbox')->where('id',$id)->increment('envios',1,
                        ['proxima_tentativa_em'=>now()->addMinutes(5),'erro_tecnico'=>null]);
                    $contagem['enviados']++;
                } catch (\Throwable) {
                    DB::table('copias_outbox')->where('id',$id)->increment('falhas',1,
                        ['proxima_tentativa_em'=>now()->addMinute(),'erro_tecnico'=>'fila_indisponivel']);
                    $contagem['falhas']++;
                    Log::error('copia_outbox_fila_indisponivel');
                }
            });
        }
        return $contagem;
    }

    public function entregar(string $id): void
    {
        if (!config('copias.auditoria_entrega')) return; // Outbox continua pendente para retomada.
        try {
            DB::transaction(function () use ($id) {
                $r = DB::table('copias_outbox')->where('id',$id)->lockForUpdate()->first();
                if (!$r || $r->entregue_em) return;
                $evento = json_decode($r->evento,true,32,JSON_THROW_ON_ERROR);
                EventoCopia::validar($evento);
                $referencias = json_decode($evento['referencias'],true,16,JSON_THROW_ON_ERROR);
                if ($evento['id'] !== $r->id || $referencias['lote_id'] !== $r->lote_id || !hash_equals($r->digest,self::digest($evento))) {
                    throw new \LogicException('Recibo divergente.');
                }
                (new GravarAuditoria($evento))->handle();
                DB::table('copias_outbox')->where('id',$id)->update(['entregue_em'=>now(),'erro_tecnico'=>null]);
            });
        } catch (\Throwable) {
            DB::table('copias_outbox')->where('id',$id)->whereNull('entregue_em')->increment('falhas',1,
                ['proxima_tentativa_em'=>now()->addMinute(),'erro_tecnico'=>'entrega_falhou']);
            throw new \RuntimeException('Entrega de auditoria de cópia pendente.');
        }
    }

    /** Sinal técnico, não autorização. O futuro publicador deve revalidar negócio sob locks. */
    public function confirmada(string $loteId): bool
    {
        if ((int)DB::table('copias_lotes')->where('id',$loteId)->value('auditoria_versao') !== 1) return false;
        $recibos = DB::table('copias_outbox')->where('lote_id',$loteId);
        if ((clone $recibos)->whereNull('entregue_em')->exists()) return false;
        $acoes = $recibos->get(['evento'])->map(fn($r)=>json_decode($r->evento,true,32,JSON_THROW_ON_ERROR))
            ->map(fn($e)=>json_decode($e['referencias'],true,16,JSON_THROW_ON_ERROR));
        return $acoes->where('acao','solicitado')->count() === 1 && $acoes->where('acao','revisado')->count() === 1
            && $acoes->where('acao','aprovado')->count() === 2;
    }
}
