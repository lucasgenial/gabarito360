<?php

namespace App\Http\Controllers\Web\Portal;

use App\Actions\Account\UpdateOwnPasswordAction;
use App\Actions\Account\UpdateOwnProfileAction;
use App\Actions\Account\UpdatePreferencesAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Account\UpdateOwnPasswordRequest;
use App\Http\Requests\Account\UpdateOwnProfileRequest;
use App\Http\Requests\Account\UpdatePreferencesRequest;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AccountController extends Controller
{
    public function profile(Request $request): View
    {
        $user = $this->actor($request);
        $user->load([
            'perfilVinculos.perfil',
            'perfilVinculos.nucleo',
            'perfilVinculos.escola',
            'lotacoes.cargo',
            'lotacoes.nucleo',
            'lotacoes.escola',
            'dispositivosMobile',
        ]);

        return view('portal.account.profile', compact('user'));
    }

    public function settings(Request $request): View
    {
        $user = $this->actor($request);
        $user->load(['preferencia', 'preferenciasNotificacao', 'dispositivosMobile']);

        return view('portal.account.settings', compact('user'));
    }

    public function updateProfile(
        UpdateOwnProfileRequest $request,
        UpdateOwnProfileAction $action,
    ): RedirectResponse {
        $action->execute($this->actor($request), $request->validated());

        return back()->with('success', 'Perfil atualizado com sucesso.');
    }

    public function updatePassword(
        UpdateOwnPasswordRequest $request,
        UpdateOwnPasswordAction $action,
    ): RedirectResponse {
        $action->execute($this->actor($request), $request->validated('password'));

        return back()->with('success', 'Senha atualizada e sessoes de API revogadas.');
    }

    public function updatePreferences(
        UpdatePreferencesRequest $request,
        UpdatePreferencesAction $action,
    ): RedirectResponse {
        $action->execute($this->actor($request), $request->validated());

        return back()->with('success', 'Preferencias atualizadas com sucesso.');
    }

    private function actor(Request $request): User
    {
        $user = $request->user();
        abort_unless($user instanceof User, 401);

        return $user;
    }
}
