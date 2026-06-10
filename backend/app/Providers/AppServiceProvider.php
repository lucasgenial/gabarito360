<?php

namespace App\Providers;

use App\Enums\PermissionCode;
use App\Models\PersonalAccessToken;
use App\Models\User;
use App\Policies\PermissionPolicy;
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

        RateLimiter::for('login', function (Request $request): Limit {
            $email = Str::lower(trim((string) $request->input('email')));

            return Limit::perMinute(max(1, (int) config('gabarito360.auth.login_max_attempts_per_minute')))
                ->by($email.'|'.$request->ip())
                ->response(fn (Request $request, array $headers) => ApiResponse::error(
                    code: 'TOO_MANY_REQUESTS',
                    message: 'Muitas requisicoes. Tente novamente mais tarde.',
                    status: 429,
                )->withHeaders($headers));
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
