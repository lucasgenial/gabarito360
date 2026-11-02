<?php
namespace App\Http\Controllers\Api;
use App\Http\Controllers\Controller;
use App\Http\Responses\ApiResponse;
use App\Services\{LotesCopia,RecebimentosMaterial,SelecaoMaterialCopia};
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;

class DecisaoMaterialCopiaController extends Controller
{
    private const VEDADOS=['usuario_id'=>'prohibited','vinculo_id'=>'prohibited','ator_id'=>'prohibited','destino_espaco_id'=>'prohibited','autorizado_por'=>'prohibited'];
    private function dados(Request $r,array $regras): array
    { RecebimentosMaterial::exigirInterface(); return $r->validate($regras+self::VEDADOS); }
    public function emitir(Request $r)
    {
        $d=$this->dados($r,['espaco_id'=>'required|integer|min:1']);
        return ApiResponse::success((new RecebimentosMaterial)->emitir($r->user(),(int)$d['espaco_id']),null,201);
    }
    public function preparar(Request $r)
    {
        $d=$this->dados($r,['espaco_id'=>'required|integer|min:1','codigo'=>'required|string|size:64',
            'provas'=>'required|array|list|min:1|max:20','provas.*'=>'integer|min:1|distinct']);
        $p=(new SelecaoMaterialCopia)->previa($r->user(),(int)$d['espaco_id'],array_map('intval',$d['provas']));
        $destino=(new RecebimentosMaterial)->consultar($r->user(),(int)$d['espaco_id'],$d['codigo']);
        return ApiResponse::success(['previa'=>$p,'recebimento'=>$destino]);
    }
    public function criar(Request $r)
    {
        $d=$this->dados($r,['espaco_id'=>'required|integer|min:1','codigo'=>'required|string|size:64',
            'provas'=>'required|array|list|min:1|max:20','provas.*'=>'integer|min:1|distinct','chave'=>'required|uuid',
            'destino_digest'=>'required|string|size:64','conteudo_digest'=>'required|string|size:64','confirmado'=>'required|accepted']);
        $id=(new LotesCopia)->criarViaRecebimento($r->user(),(int)$d['espaco_id'],$d['codigo'],$d['destino_digest'],$d['conteudo_digest'],$d['chave'],array_map('intval',$d['provas']));
        return ApiResponse::success(['lote_id'=>$id,'autoriza_execucao'=>false],null,201);
    }
    public function show(Request $r,string $id)
    {
        $d=$this->dados($r,['lado'=>'required|in:origem,destino']);
        return ApiResponse::success((new LotesCopia)->previaInterface($r->user(),$id,$d['lado']));
    }
    public function revisar(Request $r,string $id)
    {
        $d=$this->dados($r,['conteudo_digest'=>'required|string|size:64','sem_dados_pessoais'=>'required|accepted']);
        (new LotesCopia)->revisarInterface($r->user(),$id,$d['conteudo_digest'],true);
        return ApiResponse::success(['revisado'=>true,'autoriza_execucao'=>false]);
    }
    public function aprovar(Request $r,string $id)
    {
        $d=$this->dados($r,['lado'=>'required|in:origem,destino','conteudo_digest'=>'required|string|size:64',
            'valida_ate'=>'required|date_format:Y-m-d\TH:i:s\Z','confirmado'=>'required|accepted']);
        return ApiResponse::success((new LotesCopia)->aprovarInterface($r->user(),$id,$d['lado'],$d['conteudo_digest'],CarbonImmutable::parse($d['valida_ate'],'UTC'))+['autoriza_execucao'=>false]);
    }
}
