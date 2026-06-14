<?php

namespace App\Http\Controllers\Api\V2\Alunos;

use App\Http\Controllers\Api\V2\BaseApiController;
use App\Models\Aluno;
use App\Services\Alunos\FichaAlunoPdfService;
use App\Services\Audit\AuditAction;
use App\Services\Audit\AuditService;
use App\Services\Authorization\AlunoScope;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AlunoFichaController extends BaseApiController
{
    public function __invoke(
        Request $request,
        string $aluno,
        AlunoScope $scope,
        FichaAlunoPdfService $service,
        AuditService $audit,
    ): Response {
        $student = $scope->apply(Aluno::query(), $this->actor($request))
            ->with(['escola', 'matriculasTurmas.turma', 'responsaveis.responsavel'])
            ->findOrFail($aluno);

        $pdf = $service->render($student);

        $audit->record(
            action: AuditAction::STUDENT_FICHA_EXPORTED,
            entityType: 'aluno',
            entityId: $student->id,
            actorUserId: $this->actor($request)->id,
            escolaId: $student->escola_id,
        );

        return response($pdf, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="ficha-'.$student->matricula.'.pdf"',
        ]);
    }
}
