<?php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Responses\ApiResponse;
use App\Services\PreferenciasPainel;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

final class PreferenciaPainelController extends Controller
{
    public function show(Request $request, string $painel)
    {
        if ($request->query->count()) throw ValidationException::withMessages(['preferencias'=>'Não envie parâmetros nesta consulta.']);
        $turmaId=\App\Support\CatalogoPaineis::seletor($request,$painel);
        return ApiResponse::success(app(PreferenciasPainel::class)->mostrar($request->user(), $painel, $turmaId))->header('Cache-Control', 'private, no-store');
    }

    public function update(Request $request, string $painel)
    {
        if ($request->query->count()) throw ValidationException::withMessages(['preferencias'=>'Envie a configuração somente no corpo da requisição.']);
        $pedido = $request->isJson() ? $request->json()->all() : $request->request->all();
        $turmaId=\App\Support\CatalogoPaineis::seletor($request,$painel);
        return ApiResponse::success(app(PreferenciasPainel::class)->gravar($request->user(), $painel, $pedido, $turmaId))->header('Cache-Control', 'private, no-store');
    }
}
