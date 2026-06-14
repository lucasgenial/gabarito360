<?php

namespace App\Services\Auth;

use App\Models\HistoricoAcesso;
use App\Models\PersonalAccessToken;
use App\Models\SessaoUsuario;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class SessionTracker
{
    public function start(User $user, PersonalAccessToken $token, Request $request, bool $manterConectado): SessaoUsuario
    {
        $sessao = SessaoUsuario::query()->create([
            'usuario_id' => $user->id,
            'personal_access_token_id' => $token->id,
            'dispositivo' => $this->dispositivo($request),
            'ip' => $request->ip(),
            'manter_conectado' => $manterConectado,
            'criado_em' => now(),
            'ultimo_acesso_at' => now(),
        ]);

        $this->record($user->id, 'login', $request, $sessao->id);

        return $sessao;
    }

    public function endForToken(int $tokenId, Request $request): void
    {
        $sessao = SessaoUsuario::query()->ativas()->where('personal_access_token_id', $tokenId)->first();

        if ($sessao === null) {
            return;
        }

        $sessao->update(['encerrado_at' => now()]);
        $this->record($sessao->usuario_id, 'logout', $request, $sessao->id);
    }

    public function endAll(User $user): void
    {
        $user->sessoes()->ativas()->update(['encerrado_at' => now()]);
    }

    public function record(?string $usuarioId, string $evento, Request $request, ?string $sessaoId = null): void
    {
        HistoricoAcesso::query()->create([
            'usuario_id' => $usuarioId,
            'evento' => $evento,
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'sessao_id' => $sessaoId,
        ]);
    }

    private function dispositivo(Request $request): ?string
    {
        $agent = $request->userAgent();

        return $agent === null ? null : Str::limit($agent, 250, '');
    }
}
