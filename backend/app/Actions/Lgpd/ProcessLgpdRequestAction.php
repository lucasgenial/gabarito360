<?php

namespace App\Actions\Lgpd;

use App\Enums\StatusEnum;
use App\Enums\UserStatus;
use App\Models\Aluno;
use App\Models\ExecucaoDescarte;
use App\Models\SolicitacaoLgpd;
use App\Models\User;
use App\Services\Audit\AuditAction;
use App\Services\Audit\AuditService;
use Illuminate\Support\Facades\DB;

/**
 * Processa uma solicitação LGPD. Para pedidos de exclusão/anonimização, executa
 * a anonimização do titular (usuário ou aluno) — nunca exclusão física — e
 * registra a trilha em `execucoes_descarte`. Demais tipos (acesso, correção,
 * portabilidade) apenas concluem a solicitação com a decisão registrada.
 */
class ProcessLgpdRequestAction
{
    /** @var list<string> */
    private const ANONYMIZING_TYPES = ['exclusao', 'anonimizacao'];

    public function __construct(private AuditService $audit) {}

    public function execute(SolicitacaoLgpd $solicitacao, User $admin, string $decisao): SolicitacaoLgpd
    {
        return DB::transaction(function () use ($solicitacao, $admin, $decisao): SolicitacaoLgpd {
            $solicitacao = SolicitacaoLgpd::query()->lockForUpdate()->findOrFail($solicitacao->id);

            $anonimiza = in_array($solicitacao->tipo, self::ANONYMIZING_TYPES, true);

            if ($anonimiza && $solicitacao->titular_id !== null) {
                [$afetados, $detalhes] = $this->anonimizar((string) $solicitacao->titular_tipo, (string) $solicitacao->titular_id);

                ExecucaoDescarte::query()->create([
                    'solicitacao_lgpd_id' => $solicitacao->id,
                    'entidade_tipo' => $solicitacao->titular_tipo,
                    'acao' => 'anonimizacao',
                    'afetados' => $afetados,
                    'detalhes' => $detalhes,
                    'executado_por_id' => $admin->id,
                    'executado_at' => now(),
                ]);

                $this->audit->record(
                    AuditAction::LGPD_SUBJECT_ANONYMIZED,
                    (string) $solicitacao->titular_tipo,
                    (string) $solicitacao->titular_id,
                    $admin->id,
                    metadata: ['solicitacao_id' => $solicitacao->id, 'afetados' => $afetados],
                );
            }

            $solicitacao->update([
                'status' => 'concluida',
                'decisao' => $decisao,
                'concluida_at' => now(),
            ]);

            $this->audit->record(
                AuditAction::LGPD_REQUEST_PROCESSED,
                'solicitacao_lgpd',
                $solicitacao->id,
                $admin->id,
                after: $solicitacao->only(['tipo', 'status', 'concluida_at']),
                metadata: ['anonimizacao' => $anonimiza],
            );

            return $solicitacao->refresh();
        });
    }

    /**
     * @return array{0: int, 1: array<string, mixed>} [afetados, detalhes]
     */
    private function anonimizar(string $tipo, string $id): array
    {
        if ($tipo === 'aluno') {
            $aluno = Aluno::query()->lockForUpdate()->find($id);
            if ($aluno === null) {
                return [0, ['motivo' => 'titular_inexistente']];
            }

            $aluno->update([
                'nome' => 'Aluno anonimizado',
                'nome_social' => null,
                'data_nascimento' => null,
                'documento' => null,
                'genero' => null,
                'foto_arquivo_id' => null,
                'observacoes' => null,
                'status' => StatusEnum::INACTIVE,
            ]);

            return [1, ['campos' => ['nome', 'nome_social', 'data_nascimento', 'documento', 'genero', 'foto_arquivo_id', 'observacoes']]];
        }

        $user = User::query()->lockForUpdate()->find($id);
        if ($user === null) {
            return [0, ['motivo' => 'titular_inexistente']];
        }

        $user->update([
            'nome' => 'Titular anonimizado',
            'email' => 'anon-'.$user->id.'@anonimizado.invalido',
            'documento' => null,
            'telefone' => null,
            'foto_arquivo_id' => null,
            'status' => UserStatus::INACTIVE,
        ]);
        $user->tokens()->delete();

        return [1, ['campos' => ['nome', 'email', 'documento', 'telefone', 'foto_arquivo_id'], 'tokens_revogados' => true]];
    }
}
