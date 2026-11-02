<?php
namespace App\Services;
use App\Models\Usuario;
use App\Support\{ElegibilidadeCopia,Espacos,RegimeVinculos};
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

final class RecebimentosMaterial
{
    public static function exigirInterface(): void
    {
        abort_unless(config('copias.interface_ativa')===true && config('copias.ativo')===true
            && Espacos::ativo() && RegimeVinculos::obrigatorio(),404,'Área indisponível.');
    }
    public function emitir(Usuario $ator,int $destino): array
    {
        return $this->transacao(function() use($ator,$destino) {
            $c=ElegibilidadeCopia::lado($ator,$destino,'destino');
            DB::table('usuario_vinculos')->where('id',$c['vinculo_id'])->lockForUpdate()->first();
            abort_if(DB::table('copias_recebimentos')->where('vinculo_id',$c['vinculo_id'])->whereNull('lote_id')->where('valida_ate','>',now()->utc())->count()>=5,429,'Aguarde os códigos anteriores expirarem.');
            $codigo=bin2hex(random_bytes(32)); $validade=now()->utc()->addMinutes(15)->format('Y-m-d H:i:s');
            DB::table('copias_recebimentos')->insert(['id'=>(string)Str::uuid(),'codigo_hash'=>hash('sha256',$codigo),
                'destino_espaco_id'=>$destino,'ator_id'=>$c['usuario_id'],'vinculo_id'=>$c['vinculo_id'],
                'versao_acesso'=>$c['versao_acesso'],'valida_ate'=>$validade,'created_at'=>now()->utc()]);
            return ['codigo'=>$codigo,'valida_ate'=>$validade,'destino'=>$this->nome($destino),'autoriza_execucao'=>false];
        });
    }
    public function consultar(Usuario $ator,int $origem,string $codigo): array
    { return $this->transacao(fn()=>$this->apresentar($this->resolverDentro($ator,$origem,$codigo))); }
    public function resolverDentro(Usuario $ator,int $origem,string $codigo,bool $retry=false): object
    {
        self::exigirInterface(); if (DB::transactionLevel()===0) throw new \LogicException('Indicação exige transação.');
        ElegibilidadeCopia::lado($ator,$origem,'origem');
        abort_unless(preg_match('/^[a-f0-9]{64}$/D',$codigo),404,'Indicação indisponível.');
        $r=DB::table('copias_recebimentos')->where('codigo_hash',hash('sha256',$codigo))->lockForUpdate()->first();
        abort_unless($r && (int)$r->destino_espaco_id!==$origem && (!$r->lote_id || $retry)
            && (($r->lote_id && $retry) || CarbonImmutable::parse($r->valida_ate,'UTC')->isFuture()),404,'Indicação indisponível.');
        $u=Usuario::find($r->ator_id); abort_unless($u,404,'Indicação indisponível.');
        try { $c=ElegibilidadeCopia::lado($u->noVinculo($r->vinculo_id),(int)$r->destino_espaco_id,'destino'); }
        catch (\Symfony\Component\HttpKernel\Exception\HttpException) { abort(404,'Indicação indisponível.'); }
        abort_unless(hash_equals($r->versao_acesso,$c['versao_acesso']),404,'Indicação indisponível.'); return $r;
    }
    public function apresentar(object $r): array
    {
        $nome=$this->nome((int)$r->destino_espaco_id);
        return ['destino'=>$nome,'valida_ate'=>$r->valida_ate,'destino_digest'=>hash('sha256',json_encode([$r->id,$r->destino_espaco_id,$r->versao_acesso,$nome],JSON_THROW_ON_ERROR))];
    }
    private function nome(int $id): array
    { $e=DB::table('espacos')->find($id); return ['nome'=>$e->nome,'tipo'=>$e->tipo]; }
    private function transacao(callable $f): mixed
    {
        self::exigirInterface(); if (DB::transactionLevel()!==0) throw new \LogicException('Indicação exige transação própria.');
        $d=DB::connection()->getDriverName();
        if (in_array($d,['mysql','mariadb'],true)) DB::statement('SET TRANSACTION ISOLATION LEVEL SERIALIZABLE');
        elseif ($d!=='sqlite' || !app()->environment('testing')) throw new \LogicException('Indicação exige MariaDB.');
        return DB::transaction($f);
    }
}
