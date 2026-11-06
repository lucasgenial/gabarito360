<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;

final class NavegacaoLegadaController
{
    public function __invoke(Request $request)
    {
        if (!$request->isMethodSafe()) return \App\Http\Middleware\RevisaoAcesso::recusar($request);
        return redirect('/app/painel')->with('aviso', 'Abra o registro pela lista para continuar.');
    }
}
