<?php
namespace App\Support;
use App\Services\ApiClient;
use Illuminate\Support\Facades\{Cache,Crypt};
use Illuminate\Support\Str;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Contracts\Cache\LockTimeoutException;

/** Ticket curto, cifrado e vinculado à sessão/contexto. Nunca contém token da API no HTML. */
final class EnvioPedidoTitular
{
    private function chave(string $ticket): string
    {
        abort_unless(preg_match('/^[a-zA-Z0-9]{48}$/D',$ticket),419);
        return 'l4-envio:'.hash('sha256',session()->getId().'|'.session('auth_token').'|'.ContextoWeb::id().'|'.$ticket);
    }
    private function salvar(string $key,array $d): void
    {
        Cache::put($key,Crypt::encryptString(json_encode($d,JSON_THROW_ON_ERROR)),max(1,$d['expira']-now()->timestamp));
    }
    public function criar(string $acao,?string $id=null): string
    {
        $t=Str::random(48);
        $this->salvar($this->chave($t),['acao'=>$acao,'id'=>$id,'chave'=>(string)Str::uuid(),
            'expira'=>now()->addMinutes(15)->timestamp,'payload'=>null,'hash'=>null,'recibo'=>null]);
        return $t;
    }
    public function enviar(string $ticket,string $acao,?string $id,?array $dados): array
    {
        $key=$this->chave($ticket);
        try {
            return Cache::lock($key.':lock',30)->block(2,function () use ($key,$acao,$id,$dados) {
                $raw=Cache::get($key); abort_unless(is_string($raw),419,'Envio expirado. Consulte os pedidos antes de abrir outro.');
                $d=json_decode(Crypt::decryptString($raw),true,512,JSON_THROW_ON_ERROR);
                abort_unless($d['expira']>now()->timestamp && $d['acao']===$acao && $d['id']===$id,419);
                if ($dados!==null) {
                    $dados['chave']=$d['chave']; ksort($dados);
                    $hash=hash_hmac('sha256',json_encode($dados,JSON_THROW_ON_ERROR),config('app.key'));
                    abort_if($d['hash']!==null && !hash_equals($d['hash'],$hash),409,'Este envio já foi vinculado a outros dados. Consulte o protocolo antes de continuar.');
                    if ($d['hash']===null) { $d['hash']=$hash;$d['payload']=$dados;$this->salvar($key,$d); }
                }
                if ($d['recibo']!==null) return ['status'=>200,'recibo'=>$d['recibo']];
                abort_unless(is_array($d['payload']),409,'Envio ainda não preparado.');
                $alvo=$id;$payload=$d['payload'];
                if (in_array($acao,['gerar','revogar'],true)) {
                    $alvo=$payload['referencia_alvo']??null;abort_unless(is_string($alvo)&&Str::isUuid($alvo),422);unset($payload['referencia_alvo']);
                }
                $path=match($acao) {
                    'conceder'=>'/v1/autorizacoes-exportacao/pedidos/'.$id,
                    'revogar'=>'/v1/autorizacoes-exportacao/'.$alvo.'/revogar',
                    'gerar'=>'/v1/pedidos-titulares/exportacoes/'.$alvo,
                    default=>'/v1/pedidos-titulares'.($acao==='abrir'?'':'/'.$id.'/'.$acao),
                };
                try { $r=app(ApiClient::class)->post($path,$payload); }
                catch (ConnectionException) { return ['status'=>503]; }
                if (!$r->successful()) return ['status'=>in_array($r->status(),[401,403,404,409,422,429],true)?$r->status():503];
                $v=$r->json('data');
                if (!is_array($v) || !Str::isUuid($v['referencia'] ?? '')
                    || ($acao!=='gerar' && !is_numeric($v['versao'] ?? null))
                    || (!in_array($acao,['conceder','revogar'],true) && !is_string($v['situacao'] ?? null))) return ['status'=>503];
                $d['recibo']=array_intersect_key($v,array_flip(['referencia','situacao','versao','valida_ate','expira_em','revogada']));$d['payload']=null;
                $this->salvar($key,$d);
                return ['status'=>200,'recibo'=>$d['recibo']];
            });
        } catch (LockTimeoutException) { return ['status'=>503]; }
    }
}
