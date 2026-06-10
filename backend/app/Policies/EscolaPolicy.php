<?php

namespace App\Policies;

use App\Enums\StatusEnum;
use App\Models\Escola;
use App\Models\Nucleo;
use App\Models\User;
use App\Services\Authorization\SchoolScope;

class EscolaPolicy
{
    public function __construct(
        private SchoolScope $schoolScope,
    ) {}

    public function viewAny(User $user): bool
    {
        return $this->schoolScope->canAccessAny($user);
    }

    public function view(User $user, Escola $escola): bool
    {
        return $this->schoolScope->canAccessEducationCenter($user, $escola->nucleo_id);
    }

    public function create(User $user, Nucleo $nucleo): bool
    {
        return $nucleo->status === StatusEnum::ACTIVE
            && $this->schoolScope->canAccessEducationCenter($user, $nucleo->id);
    }

    public function update(User $user, Escola $escola): bool
    {
        return $this->schoolScope->canAccessEducationCenter($user, $escola->nucleo_id);
    }

    public function delete(User $user, Escola $escola): bool
    {
        return $this->schoolScope->canAccessEducationCenter($user, $escola->nucleo_id);
    }
}
