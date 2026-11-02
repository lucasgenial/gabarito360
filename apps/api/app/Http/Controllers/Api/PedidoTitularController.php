<?php
namespace App\Http\Controllers\Api;
use App\Http\Controllers\Controller;
use App\Http\Responses\ApiResponse;
use App\Services\PedidosTitulares;
use Illuminate\Http\Request;

final class PedidoTitularController extends Controller
{
    public function index(Request $r, PedidosTitulares $s)
    {
        // Sem filtros livres ou busca por identidade nesta primeira entrega.
        return ApiResponse::success($s->listar($r->user(), max(1, (int)$r->query('page',1))));
    }
    public function store(Request $r, PedidosTitulares $s)
    {
        return ApiResponse::success($s->abrir($r->user(),$r->all()),null,201);
    }
    public function show(Request $r, string $id, PedidosTitulares $s)
    {
        return ApiResponse::success($s->consultar($r->user(),$id));
    }
    public function triar(Request $r, string $id, PedidosTitulares $s)
    {
        return ApiResponse::success($s->triar($r->user(),$id,$r->all()));
    }
    public function verificar(Request $r, string $id, PedidosTitulares $s)
    {
        return ApiResponse::success($s->verificar($r->user(),$id,$r->all()));
    }
    public function decidir(Request $r, string $id, PedidosTitulares $s)
    {
        return ApiResponse::success($s->decidir($r->user(),$id,$r->all()));
    }
}
