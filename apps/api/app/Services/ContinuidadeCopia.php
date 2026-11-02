<?php
namespace App\Services;

use App\Models\Usuario;
use App\Support\{ContextoVinculo,ElegibilidadeCopia,Espacos,RegimeVinculos};
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpKernel\Exception\HttpException;

/** Fotografia orientativa para o fluxo futuro. Nunca é autorização de execução. */
final class ContinuidadeCopia
{
    public function avaliar(Usuario $origem,Usuario $destino,int $espacoOrigem,int $espacoDestino): array
    {
        abort_unless(Espacos::ativo() && RegimeVinculos::obrigatorio(),403,'Continuidade indisponível.');
        ElegibilidadeCopia::avaliar($origem,$destino,$espacoOrigem,$espacoDestino);
        $o=DB::table('espacos')->find($espacoOrigem); $d=DB::table('espacos')->find($espacoDestino);
        abort_unless(($o->tipo==='pessoal' && $d->tipo==='escola') || ($o->tipo==='escola' && $d->tipo==='pessoal'),422,'Fluxo de continuidade indisponível.');
        $entrada=$o->tipo==='pessoal'; $pessoal=$entrada?$o:$d; $escola=$entrada?$d:$o;
        $titular=$entrada?$origem:$destino;
        $v=DB::table('usuario_vinculos')->where('usuario_id',$pessoal->usuario_proprietario_id)
            ->where('espaco_id',$escola->id)->where('perfil','professor')->first();
        $situacao=$v?->situacao??'ausente';
        if ($v && $situacao==='encerrado'
            && DB::table('vinculo_convites')->where('vinculo_id',$v->id)->whereNotNull('recusado_em')->exists()
            && !DB::table('vinculo_convites')->where('vinculo_id',$v->id)->whereNotNull('aceito_em')->exists()) $situacao='convite_recusado';
        if ($v && $v->situacao==='ativo') {
            try { ContextoVinculo::resolver($titular->noVinculo($v->id)); }
            catch (HttpException) { $situacao='fora_vigencia'; }
        }
        $preparavel=$entrada?$situacao==='ativo':in_array($situacao,['ativo','encerrado'],true);
        $motivo=$preparavel?'selecao_e_autorizacoes_obrigatorias':'vinculo_institucional_indisponivel';
        return [
            'politica'=>'continuidade-1','cenario'=>$entrada?'entrada_escolar':'continuidade_pessoal',
            'situacao_institucional'=>$situacao,'autoriza_execucao'=>false,'execucao_automatica'=>false,
            'modalidades'=>[
                'material'=>['preparacao_disponivel'=>$preparavel,'motivo'=>$motivo,
                    'requisitos'=>['revisao_sem_dados_pessoais','dupla_autorizacao','auditoria']],
                'cadastro_minimo'=>['preparacao_disponivel'=>$preparavel,'motivo'=>$motivo,
                    'requisitos'=>['evidencia_cadastro','dupla_autorizacao','conciliacao','campos_obrigatorios','auditoria']],
                'resultados_identificados'=>['preparacao_disponivel'=>false,'motivo'=>'contrato_resultados_pendente'],
                'estatistica_anonima'=>['preparacao_disponivel'=>false,'motivo'=>'anonimizacao_nao_validada'],
            ],
        ];
    }
}
