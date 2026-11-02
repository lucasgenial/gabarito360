<?php
namespace App\Services;

use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

/** Inventário determinístico para comparar origem e restauração; nunca escreve. */
final class ConferenciaAuditoria
{
    private const CAMPOS = ['id','mes','ocorrido_em','usuario_real_id','usuario_visto_id','vinculo_id',
        'acesso_interno_id','espaco_id','cliente_id','escola_id','nucleo_id','perfil','metodo','rota',
        'referencias','status_http','resultado','ip_rede'];

    public function executar(?string $dias = null): array
    {
        $valor = $dias ?? config('auditoria.retencao_dias');
        if ($valor !== null && $valor !== '' && (!preg_match('/^[1-9][0-9]{0,3}$/D', (string)$valor) || (int)$valor > 3650)) {
            throw new InvalidArgumentException('Prazo inválido.');
        }
        $prazo = $valor === null || $valor === '' ? null : (int)$valor;
        $preservados = config('auditoria.meses_preservados', []);
        if (!is_array($preservados)) throw new InvalidArgumentException('Preservação inválida.');
        foreach ($preservados as $mes) if (!is_string($mes) || !preg_match('/^[0-9]{4}-(0[1-9]|1[0-2])$/D',$mes)) {
            throw new InvalidArgumentException('Preservação inválida.');
        }
        $agora = CarbonImmutable::now('UTC');
        $corte = $prazo ? $agora->subDays($prazo) : null;
        $meses = [];
        // Uma transação de leitura mantém a visão do snapshot; executar com escritores
        // parados para comparação de backup e não apenas durante atividade normal.
        DB::transaction(function () use (&$meses) {
            $atual = null; $hash = null;
            foreach (DB::table('auditoria_eventos')->select(self::CAMPOS)->orderBy('mes')->orderBy('id')->cursor() as $linha) {
                if (!preg_match('/^[0-9]{4}-(0[1-9]|1[0-2])$/D',$linha->mes)
                    || substr($linha->ocorrido_em,0,7) !== $linha->mes) throw new InvalidArgumentException('Mês inconsistente.');
                if ($atual !== $linha->mes) {
                    if ($atual !== null) $meses[$atual]['sha256'] = hash_final($hash);
                    $atual = $linha->mes; $hash = hash_init('sha256');
                    $meses[$atual] = ['quantidade'=>0];
                }
                $normal = [];
                foreach (self::CAMPOS as $campo) {
                    $v = $linha->$campo;
                    if ($campo === 'referencias') {
                        $v = json_decode($v,true,512,JSON_THROW_ON_ERROR);
                        if (!is_array($v)) throw new InvalidArgumentException('Referências inválidas.');
                        ksort($v);
                    } elseif ($campo === 'ocorrido_em') {
                        $v = CarbonImmutable::parse($v,'UTC')->format('Y-m-d H:i:s.u');
                    } else $v = $v === null ? null : (string)$v;
                    $normal[$campo] = $v;
                }
                hash_update($hash,json_encode($normal,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES|JSON_THROW_ON_ERROR)."\n");
                $meses[$atual]['quantidade']++;
            }
            if ($atual !== null) $meses[$atual]['sha256'] = hash_final($hash);
        });
        foreach ($meses as $mes => &$info) {
            $fim = CarbonImmutable::parse($mes.'-01','UTC')->addMonth();
            $info['preservado'] = in_array($mes,$preservados,true);
            $info['fora_prazo_mes_completo'] = $corte !== null && $fim <= $corte && !$info['preservado'];
        }
        unset($info);
        return ['versao'=>1,'somente_leitura'=>true,'simulacao'=>$dias !== null,'retencao_dias'=>$prazo,
            'politica_definida'=>$prazo !== null,'corte_utc'=>$corte?->format('Y-m-d H:i:s'), 'meses'=>$meses];
    }

    public function comparar(array $atual, array $anterior): bool
    {
        if (($anterior['versao'] ?? null) !== 1 || !isset($anterior['meses']) || !is_array($anterior['meses'])) return false;
        $extrair = function ($meses) {
            $r=[];
            foreach ($meses as $mes=>$m) {
                if (!is_array($m) || !isset($m['quantidade'],$m['sha256'])) return null;
                $r[$mes]=['quantidade'=>$m['quantidade'],'sha256'=>$m['sha256']];
            }
            ksort($r); return $r;
        };
        $a=$extrair($atual['meses']); $b=$extrair($anterior['meses']);
        return $a !== null && $b !== null && $a === $b;
    }
}
