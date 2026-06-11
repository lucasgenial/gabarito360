<?php

namespace App\Providers;

use App\Enums\PermissionCode;
use App\Models\Aluno;
use App\Models\DispositivoMobile;
use App\Models\Escola;
use App\Models\GabaritoOficial;
use App\Models\ImportacaoAluno;
use App\Models\ModeloCartao;
use App\Models\Nucleo;
use App\Models\PersonalAccessToken;
use App\Models\Prova;
use App\Models\Turma;
use App\Models\User;
use App\Models\UsuarioPerfil;
use App\Observers\DispositivoMobileObserver;
use App\Observers\UserAccessObserver;
use App\Observers\UsuarioPerfilObserver;
use App\Policies\AlunoPolicy;
use App\Policies\EscolaPolicy;
use App\Policies\GabaritoOficialPolicy;
use App\Policies\ImportacaoAlunoPolicy;
use App\Policies\ModeloCartaoPolicy;
use App\Policies\NucleoPolicy;
use App\Policies\PermissionPolicy;
use App\Policies\ProvaPolicy;
use App\Policies\TurmaPolicy;
use App\Policies\UserPolicy;
use App\Services\Audit\AuditAction;
use App\Services\Audit\AuditService;
use App\Services\Authorization\AuthorizationContext;
use App\Support\ApiResponse;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Sanctum::usePersonalAccessTokenModel(PersonalAccessToken::class);
        User::observe(UserAccessObserver::class);
        UsuarioPerfil::observe(UsuarioPerfilObserver::class);
        DispositivoMobile::observe(DispositivoMobileObserver::class);
        Gate::policy(Nucleo::class, NucleoPolicy::class);
        Gate::policy(Escola::class, EscolaPolicy::class);
        Gate::policy(GabaritoOficial::class, GabaritoOficialPolicy::class);
        Gate::policy(ImportacaoAluno::class, ImportacaoAlunoPolicy::class);
        Gate::policy(ModeloCartao::class, ModeloCartaoPolicy::class);
        Gate::policy(Prova::class, ProvaPolicy::class);
        Gate::policy(Aluno::class, AlunoPolicy::class);
        Gate::policy(Turma::class, TurmaPolicy::class);
        Gate::policy(User::class, UserPolicy::class);

        RateLimiter::for('login', function (Request $request): Limit {
            $email = Str::lower(trim((string) $request->input('email')));

            return Limit::perMinute(max(1, (int) config('gabarito360.auth.login_max_attempts_per_minute')))
                ->by($email.'|'.$request->ip())
                ->response(function (Request $request, array $headers) {
                    app(AuditService::class)->record(
                        action: AuditAction::LOGIN_RATE_LIMITED,
                        entityType: 'autenticacao',
                        metadata: ['motivo' => 'limite_tentativas_excedido'],
                    );

                    return ApiResponse::error(
                        code: 'TOO_MANY_REQUESTS',
                        message: 'Muitas requisicoes. Tente novamente mais tarde.',
                        status: 429,
                    )->withHeaders($headers);
                });
        });

        foreach (PermissionCode::cases() as $permission) {
            Gate::define(
                $permission->value,
                fn (User $user, ?AuthorizationContext $context = null): bool => app(PermissionPolicy::class)
                    ->allows($user, $permission, $context),
            );
        }
    }
}
