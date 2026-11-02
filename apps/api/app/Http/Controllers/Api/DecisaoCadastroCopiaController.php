<?php
namespace App\Http\Controllers\Api;
use App\Http\Controllers\Controller;
use App\Http\Responses\ApiResponse;
use App\Services\{LotesCadastroCopia,RecebimentosCopia,SelecaoCadastroCopia};
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;

class DecisaoCadastroCopiaController extends Controller
{
    private const VEDADOS=['usuario_id'=>'prohibited','vinculo_id'=>'prohibited','ator_id'=>'prohibited','destino_espaco_id'=>'prohibited','tipo_destino'=>'prohibited','autorizado_por'=>'prohibited'];
    private const SELECAO=['espaco_id'=>'required|integer|min:1','codigo'=>'required|string|size:64','turmas'=>'required|array|list|size:1',
        'turmas.*'=>'required|integer|min:1|distinct','alunos'=>'sometimes|array|list|max:20','alunos.*'=>'required|integer|min:1|distinct'];
    private function dados(Request $r,array $regras): array
    { RecebimentosCopia::exigirInterface(); return $r->validate($regras+self::VEDADOS); }
    public function emitir(Request $r)
    {
        $d=$this->dados($r,['espaco_id'=>'required|integer|min:1']);
        return ApiResponse::success((new RecebimentosCopia('cadastro_minimo'))->emitir($r->user(),(int)$d['espaco_id']),null,201);
    }
    public function preparar(Request $r)
    {
        $d=$this->dados($r,self::SELECAO);
        $destino=(new RecebimentosCopia('cadastro_minimo'))->consultar($r->user(),(int)$d['espaco_id'],$d['codigo']);
        $p=(new SelecaoCadastroCopia)->previa($r->user(),(int)$d['espaco_id'],$destino['destino']['tipo'],array_map('intval',$d['turmas']),array_map('intval',$d['alunos']??[]));
        return ApiResponse::success(['previa'=>$p,'recebimento'=>$destino]);
    }
    public function criar(Request $r)
    {
        $d=$this->dados($r,self::SELECAO+['chave'=>'required|uuid','destino_digest'=>'required|string|size:64','conteudo_digest'=>'required|string|size:64','confirmado'=>'required|accepted']);
        $id=(new LotesCadastroCopia)->criarViaRecebimento($r->user(),(int)$d['espaco_id'],$d['codigo'],$d['destino_digest'],$d['conteudo_digest'],$d['chave'],array_map('intval',$d['turmas']),array_map('intval',$d['alunos']??[]));
        return ApiResponse::success(['lote_id'=>$id,'autoriza_execucao'=>false],null,201);
    }
    public function show(Request $r,string $id)
    {
        $d=$this->dados($r,['lado'=>'required|in:origem,destino']);
        return ApiResponse::success((new LotesCadastroCopia)->previaInterface($r->user(),$id,$d['lado']));
    }
    public function evidencia(Request $r,string $id)
    {
        $d=$this->dados($r,['conteudo_digest'=>'required|string|size:64','documento'=>'required|string|max:65536','confirmado'=>'required|accepted','valida_ate'=>'required|date_format:Y-m-d\TH:i:s\Z']);
        (new LotesCadastroCopia)->evidenciaInterface($r->user(),$id,$d['conteudo_digest'],$d['documento'],true,CarbonImmutable::parse($d['valida_ate'],'UTC'));
        return ApiResponse::success(['evidencia_registrada'=>true,'autoriza_execucao'=>false]);
    }
    public function aprovar(Request $r,string $id)
    {
        $d=$this->dados($r,['lado'=>'required|in:origem,destino','conteudo_digest'=>'required|string|size:64','confirmado'=>'required|accepted',
            'valida_ate'=>'required|date_format:Y-m-d\TH:i:s\Z','conciliacao_digest'=>'nullable|string|size:64']);
        (new LotesCadastroCopia)->aprovarInterface($r->user(),$id,$d['lado'],$d['conteudo_digest'],CarbonImmutable::parse($d['valida_ate'],'UTC'),$d['conciliacao_digest']??null);
        return ApiResponse::success(['decisao_registrada'=>true,'autoriza_execucao'=>false]);
    }
}
