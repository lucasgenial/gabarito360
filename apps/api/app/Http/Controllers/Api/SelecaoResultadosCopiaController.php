<?php
namespace App\Http\Controllers\Api;
use App\Http\Controllers\Controller;
use App\Http\Responses\ApiResponse;
use App\Services\SelecaoResultadosCopia;
use Illuminate\Http\Request;

class SelecaoResultadosCopiaController extends Controller
{
    private const VEDADOS=['usuario_id'=>'prohibited','vinculo_id'=>'prohibited','ator_id'=>'prohibited','destino_espaco_id'=>'prohibited','autorizado_por'=>'prohibited','dependencias'=>'prohibited'];
    public function index(Request $r)
    {
        $d=$r->validate(['espaco_id'=>'nullable|integer|min:1','page'=>'sometimes|integer|min:1|max:100000']+self::VEDADOS);
        return ApiResponse::success((new SelecaoResultadosCopia)->catalogo($r->user(),isset($d['espaco_id'])?(int)$d['espaco_id']:null,(int)($d['page']??1)));
    }
    public function previa(Request $r)
    {
        $d=$r->validate(['espaco_id'=>'required|integer|min:1','tipo_destino'=>'required|in:pessoal,escola','notas'=>'required|array|list|min:1|max:20','notas.*'=>'required|integer|min:1|distinct']+self::VEDADOS);
        return ApiResponse::success((new SelecaoResultadosCopia)->previa($r->user(),(int)$d['espaco_id'],$d['tipo_destino'],array_map('intval',$d['notas'])));
    }
}
