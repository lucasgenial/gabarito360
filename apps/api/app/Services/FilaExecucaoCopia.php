<?php
namespace App\Services;

use App\Jobs\{ProcessarCopiaMaterial,ProcessarCopiaCadastro,ProcessarCopiaResultados};
use Illuminate\Contracts\Bus\Dispatcher;
use Illuminate\Support\Facades\DB;

final class FilaExecucaoCopia
{
    public function publicar(int $limite=20): array
    {
        if ($limite<1 || $limite>20) throw new \InvalidArgumentException('Limite entre 1 e 20.');
        $resultado=['enviados'=>0,'falhas'=>0];
        if (!config('copias.execucao_ativa') || !config('copias.ativo')) return $resultado;
        $conexao=config('auditoria.connection');
        if (config("queue.connections.$conexao.driver") !== 'redis') throw new \LogicException('Execução exige Redis.');
        $politicas=['t5-material-v1'];
        if (config('copias.cadastro_execucao_ativa')===true) $politicas[]=LotesCadastroCopia::POLITICA;
        if (config('copias.resultados_execucao_ativa')===true) $politicas[]=LotesResultadosCopia::POLITICA;
        $ids=DB::table('copias_execucoes')->whereExists(fn($q)=>$q->selectRaw('1')->from('copias_lotes')
                ->whereColumn('copias_lotes.id','copias_execucoes.lote_id')->whereIn('politica',$politicas))
            ->whereIn('estado',['pendente','aguardando_recibo'])
            ->where('proxima_tentativa_em','<=',now())->orderBy('proxima_tentativa_em')->orderBy('lote_id')->limit($limite)->pluck('lote_id');
        foreach ($ids as $id) DB::transaction(function () use ($id,$conexao,&$resultado) {
            $e=DB::table('copias_execucoes')->where('lote_id',$id)->lockForUpdate()->first();
            if (!$e || !in_array($e->estado,['pendente','aguardando_recibo'],true) || $e->proxima_tentativa_em > now()) return;
            try {
                $politica=DB::table('copias_lotes')->where('id',$id)->value('politica');
                $job=match($politica) {
                    't5-material-v1'=>new ProcessarCopiaMaterial($id),
                    LotesCadastroCopia::POLITICA=>config('copias.cadastro_execucao_ativa')===true?new ProcessarCopiaCadastro($id):null,
                    LotesResultadosCopia::POLITICA=>config('copias.resultados_execucao_ativa')===true?new ProcessarCopiaResultados($id):null,
                    default=>null,
                };
                if (!$job) return;
                app(Dispatcher::class)->dispatch($job->onConnection($conexao));
                DB::table('copias_execucoes')->where('lote_id',$id)->increment('envios',1,['proxima_tentativa_em'=>now()->addMinutes(5)]);
                $resultado['enviados']++;
            } catch (\Throwable) {
                DB::table('copias_execucoes')->where('lote_id',$id)->increment('falhas',1,
                    ['erro_tecnico'=>'fila_indisponivel','proxima_tentativa_em'=>now()->addMinute()]);
                $resultado['falhas']++;
            }
        });
        return $resultado;
    }
}
