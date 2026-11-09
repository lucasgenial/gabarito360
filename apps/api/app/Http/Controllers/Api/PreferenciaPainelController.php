<?php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Responses\ApiResponse;
use App\Services\PreferenciasPainel;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

final class PreferenciaPainelController extends Controller
{
    private function editor(Request $request): bool
    {
        return \App\Services\BootstrapEditorPainel::solicitado($request);
    }
    public function show(Request $request, string $painel)
    {
        if ($request->query->count()) throw ValidationException::withMessages(['preferencias'=>'Não envie parâmetros nesta consulta.']);
        $turmaId=\App\Support\CatalogoPaineis::seletor($request,$painel);
        $editor=$this->editor($request);$dados=app(PreferenciasPainel::class)->mostrar($request->user(), $painel, $turmaId,$editor);
        if($editor){$etag=\App\Services\BootstrapEditorPainel::etag($dados);if($request->header('If-None-Match')===$etag)return response('',304)->header('ETag',$etag)->header('Cache-Control','private, no-store');}
        $r=ApiResponse::success($dados)->header('Cache-Control', 'private, no-store');return $editor?$r->header('ETag',$etag):$r;
    }

    public function update(Request $request, string $painel)
    {
        if ($request->query->count()) throw ValidationException::withMessages(['preferencias'=>'Envie a configuração somente no corpo da requisição.']);
        $pedido = $request->isJson() ? $request->json()->all() : $request->request->all();
        $turmaId=\App\Support\CatalogoPaineis::seletor($request,$painel);
        $editor=$this->editor($request);$d=app(PreferenciasPainel::class)->gravar($request->user(), $painel, $pedido, $turmaId,$editor);
        $r=ApiResponse::success($d)->header('Cache-Control', 'private, no-store');return $editor?$r->header('ETag',\App\Services\BootstrapEditorPainel::etag($d)):$r;
    }
}
