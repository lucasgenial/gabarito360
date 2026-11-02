<?php
namespace App\Http\Controllers\Api;
use App\Http\Controllers\Controller;
use App\Http\Responses\ApiResponse;
use App\Services\SelecaoMaterialCopia;
use Illuminate\Http\Request;

class SelecaoMaterialCopiaController extends Controller
{
    private const VEDADOS=['usuario_id'=>'prohibited','vinculo_id'=>'prohibited','ator_id'=>'prohibited','destino_espaco_id'=>'prohibited','autorizado_por'=>'prohibited'];
    public function index(Request $r)
    {
        $d=$r->validate(['espaco_id'=>'nullable|integer|min:1','page'=>'sometimes|integer|min:1|max:100000']+self::VEDADOS);
        return ApiResponse::success((new SelecaoMaterialCopia)->catalogo($r->user(),isset($d['espaco_id'])?(int)$d['espaco_id']:null,(int)($d['page']??1)));
    }
    public function previa(Request $r)
    {
        $d=$r->validate(['espaco_id'=>'required|integer|min:1','provas'=>'required|array|list|min:1|max:20','provas.*'=>'required|integer|min:1|distinct']+self::VEDADOS);
        return ApiResponse::success((new SelecaoMaterialCopia)->previa($r->user(),(int)$d['espaco_id'],array_map('intval',$d['provas'])));
    }
}
