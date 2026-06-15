<?php

namespace App\Services\Notificacoes;

use App\Events\NotificationCreated;
use App\Models\Notificacao;
use App\Models\PreferenciaNotificacao;
use App\Models\User;

/**
 * Cria notificações in-app (sino do shell), respeitando as preferências do
 * usuário, e publica o evento `notification.created` no canal privado dele.
 */
class NotificationService
{
    /**
     * @param  array{dados?: array<string, mixed>, link?: ?string, nucleo_id?: ?string, escola_id?: ?string}  $opts
     */
    public function notify(User $user, string $tipo, string $titulo, string $mensagem, array $opts = []): ?Notificacao
    {
        // Canal "sistema" (in-app) pode ter sido desativado pelo usuário para o tipo.
        if (! $this->canalHabilitado($user, $tipo, 'sistema')) {
            return null;
        }

        $notificacao = Notificacao::query()->create([
            'usuario_id' => $user->id,
            'tipo' => $tipo,
            'titulo' => $titulo,
            'mensagem' => $mensagem,
            'dados' => $opts['dados'] ?? null,
            'link' => $opts['link'] ?? null,
            'nucleo_id' => $opts['nucleo_id'] ?? null,
            'escola_id' => $opts['escola_id'] ?? null,
        ]);

        NotificationCreated::dispatch($notificacao);

        return $notificacao;
    }

    private function canalHabilitado(User $user, string $evento, string $canal): bool
    {
        $pref = PreferenciaNotificacao::query()
            ->where('usuario_id', $user->id)
            ->where('evento', $evento)
            ->where('canal', $canal)
            ->first();

        return $pref?->habilitada ?? true;
    }
}
