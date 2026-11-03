<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Responses\ApiResponse;
use App\Services\GeracaoExportacaoTitular;
use Illuminate\Http\Request;

class GeracaoExportacaoTitularController extends Controller
{
    public function store(Request $request,string $id,GeracaoExportacaoTitular $servico)
    {
        return ApiResponse::success($servico->gerar($request->user(),$id,$request->all()),null,201);
    }
}
