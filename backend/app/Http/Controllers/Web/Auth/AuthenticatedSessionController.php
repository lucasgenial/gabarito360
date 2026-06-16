<?php

namespace App\Http\Controllers\Web\Auth;

use App\Enums\UserStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Models\User;
use App\Services\Audit\AuditAction;
use App\Services\Audit\AuditService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

class AuthenticatedSessionController extends Controller
{
    public function __construct(
        private AuditService $audit,
    ) {}

    public function create(): View
    {
        return view('auth.login');
    }

    public function store(LoginRequest $request): RedirectResponse
    {
        $credentials = $request->validated();
        $user = User::query()->where('email', $credentials['email'])->first();

        if (
            ! $user instanceof User
            || ! Hash::check($credentials['password'], $user->password)
            || $user->status !== UserStatus::ACTIVE
        ) {
            $this->audit->record(
                action: $user instanceof User && $user->status !== UserStatus::ACTIVE
                    ? AuditAction::LOGIN_BLOCKED_USER
                    : AuditAction::LOGIN_FAILED,
                entityType: 'usuario',
                entityId: $user?->id,
                metadata: ['cliente' => 'painel_web'],
            );

            return back()
                ->withInput($request->only('email'))
                ->withErrors(['email' => 'Credenciais invalidas.']);
        }

        Auth::login($user);
        $request->session()->regenerate();
        $user->forceFill(['ultimo_acesso_at' => now()])->save();

        $this->audit->record(
            action: AuditAction::LOGIN_SUCCEEDED,
            entityType: 'usuario',
            entityId: $user->id,
            actorUserId: $user->id,
            metadata: ['cliente' => 'painel_web'],
        );

        return redirect()->intended(route('portal.dashboard'));
    }

    public function destroy(Request $request): RedirectResponse
    {
        $user = $request->user();

        if ($user instanceof User) {
            $this->audit->record(
                action: AuditAction::LOGOUT,
                entityType: 'usuario',
                entityId: $user->id,
                actorUserId: $user->id,
                metadata: ['cliente' => 'painel_web'],
            );
        }

        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}
