<?php

namespace App\Http\Controllers\Api\V2\Me;

use App\Http\Controllers\Api\V2\BaseApiController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\Response;

class ShowFotoController extends BaseApiController
{
    public function __invoke(Request $request): Response
    {
        $arquivo = $request->user()->fotoArquivo;

        abort_if($arquivo === null, 404);

        return Storage::disk($arquivo->disco)->response(
            $arquivo->caminho,
            $arquivo->nome_original,
            ['Content-Type' => $arquivo->mime],
        );
    }
}
