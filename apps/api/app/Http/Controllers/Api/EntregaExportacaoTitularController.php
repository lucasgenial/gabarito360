<?php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Responses\ApiResponse;
use App\Services\EntregaExportacaoTitular;
use Illuminate\Http\Request;

final class EntregaExportacaoTitularController extends Controller
{
    public function show(Request $r,string $id,EntregaExportacaoTitular $s)
    {
        return ApiResponse::success($s->consultar($r->user(),$id));
    }
    public function arquivo(Request $r,string $id,EntregaExportacaoTitular $s)
    {
        abort_unless($r->isMethod('GET'),405);
        $arquivo=$s->baixar($r->user(),$id);
        return response($arquivo['documento'],200,[
            'Content-Type'=>'application/json; charset=UTF-8',
            'Content-Disposition'=>'attachment; filename="exportacao-'.$arquivo['referencia'].'.json"',
            'Cache-Control'=>'private, no-store','Pragma'=>'no-cache','Expires'=>'0','X-Content-Type-Options'=>'nosniff',
        ]);
    }
}
