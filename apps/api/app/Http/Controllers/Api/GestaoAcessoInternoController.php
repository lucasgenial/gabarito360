<?php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Responses\ApiResponse;
use App\Models\Usuario;
use App\Services\GestaoAcessosInternos;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class GestaoAcessoInternoController extends Controller
{
    public function index(Request $r, GestaoAcessosInternos $gestao)
    {
        $gestao->autorizar($r->user());
        $lista = DB::table('usuario_acessos_internos')->whereIn('perfil',['suporte','financeiro'])
            ->where('usuario_id','<>',$r->user()->id)
            ->whereNotIn('usuario_id',DB::table('usuario_acessos_internos')->select('usuario_id')->where('perfil','desenvolvedor'))
            ->orderBy('id')->paginate(20);
        $usuarios = Usuario::whereIn('id',collect($lista->items())->pluck('usuario_id'))->get()->keyBy('id');
        $lista->through(function ($a) use ($usuarios) { $a->nome = $usuarios[$a->usuario_id]->nome; return $a; });
        return ApiResponse::success($lista);
    }
    public function store(Request $r, GestaoAcessosInternos $gestao)
    {
        return ApiResponse::success($gestao->conceder($r->user(),$r->all()),null,201);
    }
    public function update(Request $r, int $id, GestaoAcessosInternos $gestao)
    {
        return ApiResponse::success($gestao->atualizar($r->user(),$id,$r->all()));
    }
}
