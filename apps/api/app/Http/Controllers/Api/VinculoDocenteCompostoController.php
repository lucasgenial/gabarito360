<?php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Responses\ApiResponse;
use App\Services\VinculoDocenteComposto;
use Illuminate\Http\Request;

class VinculoDocenteCompostoController extends Controller
{
    public function opcoes(Request $r,int $id,VinculoDocenteComposto $servico)
    {
        return ApiResponse::success($servico->opcoes($r->user(),$id));
    }

    public function store(Request $r,int $id,VinculoDocenteComposto $servico)
    {
        abort_if(array_diff(array_keys($r->all()),['disciplina_id','professor_vinculo_id','chave_idempotencia']),422,'Envie somente os campos desta operação.');
        $d=$r->validate(['disciplina_id'=>'required|integer|min:1','professor_vinculo_id'=>'required|integer|min:1','chave_idempotencia'=>'required|uuid']);
        return ApiResponse::success($servico->executar($r->user(),$id,$d),null,201);
    }
}
