<?php

namespace App\Providers;

use App\Enums\PermissionCode;
use App\Models\User;
use App\Policies\PermissionPolicy;
use App\Services\Authorization\AuthorizationContext;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

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
        foreach (PermissionCode::cases() as $permission) {
            Gate::define(
                $permission->value,
                fn (User $user, ?AuthorizationContext $context = null): bool => app(PermissionPolicy::class)
                    ->allows($user, $permission, $context),
            );
        }
    }
}
