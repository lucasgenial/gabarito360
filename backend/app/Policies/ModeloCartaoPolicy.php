<?php

namespace App\Policies;

use App\Enums\ModeloCartaoStatus;
use App\Enums\StatusEnum;
use App\Models\ModeloCartao;
use App\Models\Nucleo;
use App\Models\User;
use App\Services\Authorization\ModeloCartaoScope;

class ModeloCartaoPolicy
{
    public function __construct(
        private ModeloCartaoScope $scope,
    ) {}

    public function viewAny(User $user): bool
    {
        return $this->scope->canAccessAny($user);
    }

    public function view(User $user, ModeloCartao $model): bool
    {
        return $this->scope->canView($user, $model);
    }

    public function create(User $user, ?Nucleo $educationCenter = null): bool
    {
        return ($educationCenter === null || (! $educationCenter->trashed() && $educationCenter->status === StatusEnum::ACTIVE))
            && $this->scope->canCreate($user, $educationCenter);
    }

    public function update(User $user, ModeloCartao $model): bool
    {
        return $model->status === ModeloCartaoStatus::DRAFT
            && $this->scope->canManage($user, $model);
    }

    public function approve(User $user, ModeloCartao $model): bool
    {
        $model->loadMissing('nucleo');

        return $model->status === ModeloCartaoStatus::DRAFT
            && (
                $model->nucleo_id === null
                || ($model->nucleo instanceof Nucleo && $model->nucleo->status === StatusEnum::ACTIVE)
            )
            && $this->scope->canManage($user, $model);
    }

    public function delete(User $user, ModeloCartao $model): bool
    {
        return $model->status !== ModeloCartaoStatus::INACTIVE
            && $this->scope->canManage($user, $model);
    }
}
