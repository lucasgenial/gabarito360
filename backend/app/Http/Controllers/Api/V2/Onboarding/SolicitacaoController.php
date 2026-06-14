<?php

namespace App\Http\Controllers\Api\V2\Onboarding;

use App\Http\Controllers\Api\V2\BaseApiController;
use App\Http\Requests\Api\V2\Onboarding\CriarSolicitacaoRequest;
use App\Models\Consentimento;
use App\Models\SolicitacaoCadastro;
use App\Services\Audit\AuditAction;
use App\Services\Audit\AuditService;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

class SolicitacaoController extends BaseApiController
{
    public function __construct(
        private AuditService $audit,
    ) {}

    public function __invoke(CriarSolicitacaoRequest $request): Response
    {
        $data = $request->validated();
        $cpf = (string) preg_replace('/\D/', '', $data['cpf']);

        DB::transaction(function () use ($data, $cpf, $request): void {
            $consentimento = Consentimento::query()->create([
                'titular_tipo' => 'solicitacao_cadastro',
                'finalidade' => 'onboarding_lgpd',
                'concedido' => true,
                'ip' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ]);

            $solicitacao = SolicitacaoCadastro::query()->create([
                'nome' => $data['nome'],
                'documento_hash' => hash('sha256', $cpf),
                'documento_mascarado' => $this->mascarar($cpf),
                'email' => $data['email'],
                'perfil_codigo' => $data['perfil'],
                'status' => 'pendente',
                'consentimento_id' => $consentimento->id,
            ]);

            $consentimento->update(['titular_id' => $solicitacao->id]);

            $this->audit->record(
                action: AuditAction::ONBOARDING_REQUESTED,
                entityType: 'solicitacao_cadastro',
                entityId: $solicitacao->id,
                metadata: ['perfil' => $data['perfil']],
            );
        });

        return response()->noContent(Response::HTTP_ACCEPTED);
    }

    private function mascarar(string $cpf): string
    {
        return '***.'.substr($cpf, 3, 3).'.'.substr($cpf, 6, 3).'-**';
    }
}
