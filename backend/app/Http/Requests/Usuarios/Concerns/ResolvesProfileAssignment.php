<?php

namespace App\Http\Requests\Usuarios\Concerns;

use App\Enums\AccessScope;
use App\Enums\UserRole;
use App\Models\Escola;
use App\Models\Nucleo;
use App\Models\Perfil;
use App\Models\User;
use App\Services\Authorization\UserAdministrationScope;
use Illuminate\Support\Str;
use Illuminate\Validation\Validator;

trait ResolvesProfileAssignment
{
    protected function assignmentIsAuthorized(User $actor, UserAdministrationScope $scope): bool
    {
        foreach (['perfil_id', 'nucleo_id', 'escola_id'] as $field) {
            $value = $this->input($field);

            if ($value !== null && (! is_string($value) || ! Str::isUuid($value))) {
                return $scope->canAccessAny($actor);
            }
        }

        $profile = $this->profile();

        if (! $profile instanceof Perfil) {
            return $scope->hasGlobalAccess($actor);
        }

        $expectedScope = UserRole::tryFrom($profile->codigo)?->allowedScope();

        if (
            ($expectedScope === AccessScope::EDUCATION_CENTER && $this->input('nucleo_id') === null)
            || (in_array($expectedScope, [AccessScope::SCHOOL, AccessScope::OPERATIONAL], strict: true)
                && $this->input('escola_id') === null)
        ) {
            return $scope->canAccessAny($actor);
        }

        $educationCenter = $this->educationCenter();
        $school = $this->school();

        if (
            ($this->input('nucleo_id') !== null && ! $educationCenter instanceof Nucleo)
            || ($this->input('escola_id') !== null && ! $school instanceof Escola)
        ) {
            return $scope->hasGlobalAccess($actor);
        }

        return $scope->canAssign($actor, $profile, $educationCenter, $school);
    }

    protected function addAssignmentValidation(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $profile = $this->profile();
            $role = $profile instanceof Perfil ? UserRole::tryFrom($profile->codigo) : null;

            if ($role === null) {
                return;
            }

            match ($role->allowedScope()) {
                AccessScope::GLOBAL => $this->validateGlobalAssignment($validator),
                AccessScope::EDUCATION_CENTER => $this->validateEducationCenterAssignment($validator),
                AccessScope::SCHOOL, AccessScope::OPERATIONAL => $this->validateSchoolAssignment($validator),
            };
        });
    }

    protected function profile(): ?Perfil
    {
        $id = $this->input('perfil_id');

        return is_string($id) && Str::isUuid($id)
            ? Perfil::query()->find($id)
            : null;
    }

    protected function educationCenter(): ?Nucleo
    {
        $id = $this->input('nucleo_id');

        return is_string($id) && Str::isUuid($id)
            ? Nucleo::query()->find($id)
            : null;
    }

    protected function school(): ?Escola
    {
        $id = $this->input('escola_id');

        return is_string($id) && Str::isUuid($id)
            ? Escola::query()->with('nucleo')->find($id)
            : null;
    }

    /** @return array{perfil_id: mixed, nucleo_id: mixed, escola_id: mixed} */
    public function assignmentAttributes(): array
    {
        return [
            'perfil_id' => $this->input('perfil_id'),
            'nucleo_id' => $this->input('nucleo_id'),
            'escola_id' => $this->input('escola_id'),
        ];
    }

    private function validateGlobalAssignment(Validator $validator): void
    {
        if ($this->input('nucleo_id') !== null) {
            $validator->errors()->add('nucleo_id', 'Perfil global nao aceita nucleo.');
        }

        if ($this->input('escola_id') !== null) {
            $validator->errors()->add('escola_id', 'Perfil global nao aceita escola.');
        }
    }

    private function validateEducationCenterAssignment(Validator $validator): void
    {
        if ($this->input('nucleo_id') === null) {
            $validator->errors()->add('nucleo_id', 'Perfil de nucleo exige nucleo.');
        }

        if ($this->input('escola_id') !== null) {
            $validator->errors()->add('escola_id', 'Perfil de nucleo nao aceita escola.');
        }
    }

    private function validateSchoolAssignment(Validator $validator): void
    {
        if ($this->input('escola_id') === null) {
            $validator->errors()->add('escola_id', 'Perfil exige escola para administracao do usuario.');
        }

        if ($this->input('nucleo_id') !== null) {
            $validator->errors()->add('nucleo_id', 'Perfil de escola ou operacional nao aceita nucleo.');
        }
    }
}
