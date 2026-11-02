<?php
namespace App\Http\Controllers\Api;
use App\Http\Controllers\Controller;
use App\Http\Responses\ApiResponse;
use App\Services\SelecaoCadastroCopia;
use Illuminate\Http\Request;

class SelecaoCadastroCopiaController extends Controller
{
    private const VEDADOS=['usuario_id'=>'prohibited','vinculo_id'=>'prohibited','ator_id'=>'prohibited','destino_espaco_id'=>'prohibited','autorizado_por'=>'prohibited'];
    public function index(Request $r)
    {
        $d=$r->validate(['espaco_id'=>'nullable|integer|min:1','turma_id'=>'nullable|integer|min:1',
            'page'=>'sometimes|integer|min:1|max:100000','alunos_page'=>'sometimes|integer|min:1|max:100000']+self::VEDADOS);
        return ApiResponse::success((new SelecaoCadastroCopia)->catalogo($r->user(),isset($d['espaco_id'])?(int)$d['espaco_id']:null,
            isset($d['turma_id'])?(int)$d['turma_id']:null,(int)($d['page']??1),(int)($d['alunos_page']??1)));
    }
    public function previa(Request $r)
    {
        $d=$r->validate(['espaco_id'=>'required|integer|min:1','tipo_destino'=>'required|in:pessoal,escola',
            'turmas'=>'required|array|list|size:1','turmas.*'=>'required|integer|min:1|distinct',
            'alunos'=>'sometimes|array|list|max:20','alunos.*'=>'required|integer|min:1|distinct']+self::VEDADOS);
        return ApiResponse::success((new SelecaoCadastroCopia)->previa($r->user(),(int)$d['espaco_id'],$d['tipo_destino'],
            array_map('intval',$d['turmas']),array_map('intval',$d['alunos']??[])));
    }
}
