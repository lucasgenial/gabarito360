<?php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Responses\ApiResponse;
use App\Support\ContextoVinculo;
use App\Support\RegimeVinculos;
use App\Support\TokenVinculo;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpKernel\Exception\HttpException;

class SelecaoVinculoController extends Controller
{
    public function index(Request $request)
    {
        abort_unless(RegimeVinculos::obrigatorio(), 404);
        $u = $request->user();
        TokenVinculo::identidade($u);
        $lista = [];
        foreach ($u->vinculos()->orderBy('id')->pluck('id') as $id) {
            try { $v = ContextoVinculo::resolver($u->noVinculo($id)); }
            catch (HttpException $e) { if ($e->getStatusCode() !== 403) throw $e; continue; }
            $lista[] = ['id' => $v->id, 'perfil' => $v->perfil, 'espaco' => ['id' => $v->espaco_id, 'nome' => $v->espaco->nome, 'tipo' => $v->espaco->tipo]];
        }
        return ApiResponse::success($lista);
    }

    public function selecionar(Request $request)
    {
        abort_unless(RegimeVinculos::obrigatorio(), 404);
        $data = $request->validate(['vinculo_id' => 'required|integer|min:1']);
        return DB::transaction(function () use ($request, $data) {
            $u = $request->user();
            $pai = TokenVinculo::identidade($u);
            abort_unless($pai->newQuery()->whereKey($pai->id)->lockForUpdate()->exists(), 403);
            $contexto = $u->noVinculo((int) $data['vinculo_id']);
            $v = ContextoVinculo::resolver($contexto);
            $permissoes = ContextoVinculo::permissoes($contexto);
            $token = $u->createToken(TokenVinculo::NOME, ['vinculo:'.$v->id, 'identidade:'.$pai->id]);
            return ApiResponse::success(['token' => $token->plainTextToken, 'vinculo_id' => $v->id,
                'perfil' => $v->perfil, 'permissoes' => $permissoes,
                'espaco' => ['id' => $v->espaco_id, 'nome' => $v->espaco->nome, 'tipo' => $v->espaco->tipo]], null, 201);
        });
    }
}
