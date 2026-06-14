<?php

namespace App\Http\Controllers\Api\V2\Alunos;

use App\Http\Controllers\Api\V2\BaseApiController;
use App\Models\Aluno;
use App\Services\Authorization\AlunoScope;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\Response;

class AlunoFotoController extends BaseApiController
{
    public function __invoke(Request $request, string $aluno, AlunoScope $scope): Response
    {
        $student = $scope->apply(Aluno::query(), $this->actor($request))->findOrFail($aluno);
        $arquivo = $student->fotoArquivo;

        abort_if($arquivo === null, 404);

        return Storage::disk($arquivo->disco)->response(
            $arquivo->caminho,
            $arquivo->nome_original,
            ['Content-Type' => $arquivo->mime],
        );
    }
}
