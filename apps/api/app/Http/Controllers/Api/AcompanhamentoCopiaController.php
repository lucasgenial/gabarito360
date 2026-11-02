<?php
namespace App\Http\Controllers\Api;
use App\Http\Controllers\Controller;
use App\Http\Responses\ApiResponse;
use App\Services\AcompanhamentoCopias;
use Illuminate\Http\Request;

class AcompanhamentoCopiaController extends Controller
{
    public function index(Request $r)
    {
        $d=$r->validate(['lado'=>'required|in:origem,destino','espaco_id'=>'nullable|integer|min:1','page'=>'sometimes|integer|min:1|max:100000',
            'usuario_id'=>'prohibited','vinculo_id'=>'prohibited','ator_id'=>'prohibited']);
        return ApiResponse::success((new AcompanhamentoCopias)->listar($r->user(),$d['lado'],isset($d['espaco_id'])?(int)$d['espaco_id']:null,(int)($d['page']??1)));
    }
}
