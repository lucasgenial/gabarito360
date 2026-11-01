<?php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Responses\ApiResponse;
use App\Support\{ContextoInterno, TokenInterno, TokenVinculo};
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpKernel\Exception\HttpException;

class SelecaoInternaController extends Controller
{
    public function index(Request $request)
    {
        abort_unless(TokenInterno::habilitado(), 404);
        $u = $request->user();
        TokenVinculo::identidade($u);
        $lista = [];
        foreach (DB::table('usuario_acessos_internos')->where('usuario_id', $u->id)->orderBy('id')->get() as $a) {
            try { ContextoInterno::resolver($u->noAcessoInterno($a->id, $a->versao_autorizacao)); }
            catch (HttpException $e) { if ($e->getStatusCode() !== 403) throw $e; continue; }
            $lista[] = ['id' => $a->id, 'perfil' => $a->perfil];
        }
        return ApiResponse::success($lista);
    }

    public function selecionar(Request $request)
    {
        abort_unless(TokenInterno::habilitado(), 404);
        $data = $request->validate(['acesso_interno_id' => 'required|integer|min:1']);
        return DB::transaction(function () use ($request, $data) {
            $u = $request->user();
            $pai = TokenVinculo::identidade($u);
            abort_unless($pai->newQuery()->whereKey($pai->id)->lockForUpdate()->exists(), 403);
            $a = DB::table('usuario_acessos_internos')->where('id', $data['acesso_interno_id'])
                ->where('usuario_id', $u->id)->lockForUpdate()->first();
            abort_unless($a, 403);
            $contexto = $u->noAcessoInterno($a->id, $a->versao_autorizacao);
            $permissoes = ContextoInterno::permissoes($contexto);
            $token = $u->createToken(TokenInterno::NOME, ['interno:'.$a->id, 'versao:'.$a->versao_autorizacao, 'identidade:'.$pai->id]);
            return ApiResponse::success(['token' => $token->plainTextToken, 'tipo_contexto' => 'interno',
                'acesso_interno_id' => $a->id, 'perfil' => $a->perfil, 'permissoes' => $permissoes], null, 201);
        });
    }
}
