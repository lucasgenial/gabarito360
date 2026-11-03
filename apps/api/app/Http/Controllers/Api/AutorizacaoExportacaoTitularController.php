<?php
namespace App\Http\Controllers\Api;
use App\Http\Controllers\Controller;
use App\Http\Responses\ApiResponse;
use App\Services\AutorizacoesExportacaoTitular;
use Illuminate\Http\Request;
final class AutorizacaoExportacaoTitularController extends Controller
{
    public function show(Request $r,string $id,AutorizacoesExportacaoTitular $s)
    { return ApiResponse::success($s->consultarPedido($r->user(),$id)); }
    public function store(Request $r,string $id,AutorizacoesExportacaoTitular $s)
    { return ApiResponse::success($s->conceder($r->user(),$id,$r->all()),null,201); }
    public function revogar(Request $r,string $id,AutorizacoesExportacaoTitular $s)
    { return ApiResponse::success($s->revogar($r->user(),$id,$r->all())); }
}
