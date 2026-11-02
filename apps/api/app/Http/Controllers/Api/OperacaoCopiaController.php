<?php
namespace App\Http\Controllers\Api;
use App\Http\Controllers\Controller;
use App\Http\Responses\ApiResponse;
use App\Services\OperacaoCopia;
use Illuminate\Http\Request;

class OperacaoCopiaController extends Controller
{
    private function dados(Request $r,array $regras=[]): array
    {
        return $r->validate($regras+['ator_id'=>'prohibited','usuario_id'=>'prohibited','vinculo_id'=>'prohibited',
            'destino_espaco_id'=>'prohibited','espaco_id'=>'prohibited','finalidade'=>'prohibited']);
    }
    public function show(Request $r,string $id)
    {
        $d=$this->dados($r,['lado'=>'required|in:origem,destino']);
        return ApiResponse::success((new OperacaoCopia)->consultar($r->user(),$id,$d['lado']));
    }
    public function solicitar(Request $r,string $id)
    {
        $this->dados($r,['confirmado'=>'required|accepted','lado'=>'prohibited']);
        (new OperacaoCopia)->solicitar($r->user(),$id);
        return ApiResponse::success(['solicitacao_registrada'=>true],null,202);
    }
    public function revogar(Request $r,string $id)
    {
        $d=$this->dados($r,['lado'=>'required|in:origem,destino','confirmado'=>'required|accepted']);
        (new OperacaoCopia)->revogar($r->user(),$id,$d['lado']);
        return ApiResponse::success(['revogacao_registrada'=>true]);
    }
    public function acervo(Request $r,string $id)
    {
        $this->dados($r,['lado'=>'prohibited']);
        return ApiResponse::success((new OperacaoCopia)->acervo($r->user(),$id));
    }
}
