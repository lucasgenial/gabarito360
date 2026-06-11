<?php

namespace App\Policies;

use App\Enums\GabaritoOficialStatus;
use App\Enums\ProvaStatus;
use App\Models\GabaritoOficial;
use App\Models\User;
use App\Services\Authorization\ProvaScope;

class GabaritoOficialPolicy
{
    public function __construct(
        private ProvaScope $scope,
    ) {}

    public function view(User $user, GabaritoOficial $answerKey): bool
    {
        $answerKey->loadMissing('prova');

        return $this->scope->canView($user, $answerKey->prova);
    }

    public function update(User $user, GabaritoOficial $answerKey): bool
    {
        $answerKey->loadMissing('prova');

        return $answerKey->status === GabaritoOficialStatus::DRAFT
            && $answerKey->prova->status === ProvaStatus::DRAFT
            && $this->scope->canManage($user, $answerKey->prova);
    }
}
