<?php

namespace App\Support;

use App\Models\Usuario;
use App\Services\ConferenciaAuditoria;
use Illuminate\Support\Str;

/** Envelope v1 de domínio; nunca simula requisição HTTP. */
final class EventoCopia
{
    public const ACOES = ['solicitado','revisado','aprovado','revogado','bloqueado','execucao_solicitada',
        'item_publicado','item_reutilizado','publicacao_concluida','execucao_falhou'];

    /** Contexto histórico da intenção, não credencial: o worker revalida acesso antes de copiar. */
    public static function sistema(array $base, string $acao, ?int $item = null): array
    {
        self::validar($base);
        $r=json_decode($base['referencias'],true,16,JSON_THROW_ON_ERROR);
        $r['versao']=2; $r['acao']=$acao; $r['executor']='worker_copia'; $r['item_id']=$item;
        $base['id']=(string)Str::uuid(); $base['mes']=now()->utc()->format('Y-m');
        $base['ocorrido_em']=now()->utc()->format('Y-m-d H:i:s.u');
        $base['referencias']=json_encode($r,JSON_THROW_ON_ERROR);
        self::validar($base);
        return $base;
    }

    public static function criar(object $lote, Usuario $ator, string $acao, ?string $decisao): array
    {
        $v = ContextoVinculo::resolver($ator);
        $agora = now()->utc();
        $evento = array_fill_keys(ConferenciaAuditoria::CAMPOS,null);
        $evento = array_replace($evento, [
            'id'=>(string)Str::uuid(), 'mes'=>$agora->format('Y-m'), 'ocorrido_em'=>$agora->format('Y-m-d H:i:s.u'),
            'usuario_real_id'=>(int)$ator->id, 'usuario_visto_id'=>(int)$ator->id, 'vinculo_id'=>(int)$v->id,
            'espaco_id'=>(int)$v->espaco_id, 'cliente_id'=>(int)$v->espaco->cliente_id,
            'escola_id'=>$v->espaco->escola_id, 'nucleo_id'=>$v->espaco->nucleo_id, 'perfil'=>$v->perfil,
            'resultado'=>'registrado',
            'referencias'=>json_encode(['tipo'=>'copia','versao'=>1,'acao'=>$acao,'lote_id'=>$lote->id,
                'decisao_id'=>$decisao,'origem_espaco_id'=>(int)$lote->origem_espaco_id,
                'destino_espaco_id'=>(int)$lote->destino_espaco_id,'versao_acesso'=>ContextoVinculo::versaoAcesso($ator),
                'executor'=>$acao === 'bloqueado' ? 'revalidacao' : 'aplicacao'],JSON_THROW_ON_ERROR),
        ]);
        self::validar($evento);
        return $evento;
    }

    public static function validar(array $evento): void
    {
        if (count($evento) !== count(ConferenciaAuditoria::CAMPOS) || array_diff(array_keys($evento),ConferenciaAuditoria::CAMPOS)
            || !is_int($evento['usuario_real_id'] ?? null) || $evento['usuario_real_id'] <= 0
            || ($evento['usuario_visto_id'] ?? null) !== $evento['usuario_real_id']
            || !is_int($evento['vinculo_id'] ?? null) || $evento['vinculo_id'] <= 0
            || !is_string($evento['mes'] ?? null) || !preg_match('/^[0-9]{4}-(0[1-9]|1[0-2])$/D',$evento['mes'])
            || !is_string($evento['ocorrido_em'] ?? null) || substr($evento['ocorrido_em'],0,7) !== $evento['mes']
            || ($evento['ip_rede'] ?? null) !== null || ($evento['acesso_interno_id'] ?? null) !== null) {
            throw new \LogicException('Contexto de evento de cópia inválido.');
        }
        $r = json_decode($evento['referencias'] ?? '',true,16,JSON_THROW_ON_ERROR);
        $campos = ['tipo','versao','acao','lote_id','decisao_id','origem_espaco_id','destino_espaco_id','versao_acesso','executor'];
        if (is_array($r) && ($r['versao'] ?? null) === 2) {
            $campos[]='item_id';
            if (!array_key_exists('item_id',$r) || ($r['item_id'] !== null && (!is_int($r['item_id']) || $r['item_id'] < 1))) {
                throw new \LogicException('Referência de unidade inválida.');
            }
        }
        if (!is_array($r) || count($r) !== count($campos) || array_diff(array_keys($r),$campos)
            || $r['tipo'] !== 'copia' || !in_array($r['versao'],[1,2],true) || !in_array($r['acao'],self::ACOES,true)
            || !Str::isUuid($evento['id'] ?? '') || !Str::isUuid($r['lote_id'])
            || ($r['decisao_id'] !== null && !Str::isUuid($r['decisao_id']))
            || !is_int($r['origem_espaco_id']) || !is_int($r['destino_espaco_id'])
            || $r['origem_espaco_id'] <= 0 || $r['destino_espaco_id'] <= 0 || $r['origem_espaco_id'] === $r['destino_espaco_id']
            || !is_string($r['versao_acesso']) || !preg_match('/^[a-f0-9]{64}$/D',$r['versao_acesso'])
            || !in_array($r['executor'],['aplicacao','revalidacao','worker_copia'],true)
            || ($evento['metodo'] ?? null) !== null || ($evento['rota'] ?? null) !== null || ($evento['status_http'] ?? null) !== null
            || ($evento['resultado'] ?? null) !== 'registrado') throw new \LogicException('Envelope de cópia inválido.');
    }
}
