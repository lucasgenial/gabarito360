<?php
namespace App\Http\Controllers\Api;
use App\Http\Controllers\Controller;
use App\Http\Responses\ApiResponse;
use App\Services\{LotesResultadosCopia,RecebimentosCopia};
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;

class DecisaoResultadosCopiaController extends Controller
{
    private const VEDADOS=['usuario_id'=>'prohibited','vinculo_id'=>'prohibited','ator_id'=>'prohibited','destino_espaco_id'=>'prohibited','tipo_destino'=>'prohibited','autorizado_por'=>'prohibited'];
    private const SELECAO=['espaco_id'=>'required|integer|min:1','codigo'=>'required|string|size:64','notas'=>'required|array|list|min:1|max:20','notas.*'=>'required|integer|min:1|distinct'];
    private function dados(Request $r,array $regras): array
    { RecebimentosCopia::exigirInterface(); return $r->validate($regras+self::VEDADOS); }
    public function emitir(Request $r)
    {
        $d=$this->dados($r,['espaco_id'=>'required|integer|min:1']);
        return ApiResponse::success((new RecebimentosCopia('resultados_identificados'))->emitir($r->user(),(int)$d['espaco_id']),null,201);
    }
    public function preparar(Request $r)
    {
        $d=$this->dados($r,self::SELECAO);
        return ApiResponse::success((new LotesResultadosCopia)->prepararInterface($r->user(),(int)$d['espaco_id'],$d['codigo'],array_map('intval',$d['notas'])));
    }
    public function criar(Request $r)
    {
        $regras=self::SELECAO+['chave'=>'required|uuid','destino_digest'=>'required|string|size:64','conteudo_digest'=>'required|string|size:64',
            'confirmado'=>'required|accepted','dependencias_confirmadas'=>'required|accepted','legado_reconhecido'=>'required|accepted','dependencias'=>'required|array:provas,alunos,turmas'];
        foreach(['provas','alunos','turmas'] as $g) { $regras['dependencias.'.$g]='required|array|list|min:1|max:20'; $regras['dependencias.'.$g.'.*']='required|integer|min:1|distinct'; }
        $d=$this->dados($r,$regras); $deps=[]; foreach(['provas','alunos','turmas'] as $g) $deps[$g]=array_map('intval',$d['dependencias'][$g]);
        $id=(new LotesResultadosCopia)->criarViaRecebimento($r->user(),(int)$d['espaco_id'],$d['codigo'],$d['destino_digest'],$d['conteudo_digest'],$d['chave'],array_map('intval',$d['notas']),$deps);
        return ApiResponse::success(['lote_id'=>$id,'autoriza_execucao'=>false],null,201);
    }
    public function show(Request $r,string $id)
    {
        $d=$this->dados($r,['lado'=>'required|in:origem,destino']);
        return ApiResponse::success((new LotesResultadosCopia)->previaInterface($r->user(),$id,$d['lado']));
    }
    public function evidencia(Request $r,string $id)
    {
        $d=$this->dados($r,['conteudo_digest'=>'required|string|size:64','documento'=>'required|string|max:65536','guarda'=>'required|string|max:2000',
            'preservacao'=>'required|string|max:2000','confirmado'=>'required|accepted','legado_reconhecido'=>'required|accepted','valida_ate'=>'required|date_format:Y-m-d\TH:i:s\Z']);
        (new LotesResultadosCopia)->evidenciaInterface($r->user(),$id,$d['conteudo_digest'],$d['documento'],$d['guarda'],$d['preservacao'],true,true,CarbonImmutable::parse($d['valida_ate'],'UTC'));
        return ApiResponse::success(['evidencia_registrada'=>true,'autoriza_execucao'=>false]);
    }
    public function aprovar(Request $r,string $id)
    {
        $d=$this->dados($r,['lado'=>'required|in:origem,destino','conteudo_digest'=>'required|string|size:64','confirmado'=>'required|accepted',
            'legado_reconhecido'=>'required|accepted','valida_ate'=>'required|date_format:Y-m-d\TH:i:s\Z']);
        (new LotesResultadosCopia)->aprovarInterface($r->user(),$id,$d['lado'],$d['conteudo_digest'],true,CarbonImmutable::parse($d['valida_ate'],'UTC'));
        return ApiResponse::success(['decisao_registrada'=>true,'autoriza_execucao'=>false]);
    }
}
