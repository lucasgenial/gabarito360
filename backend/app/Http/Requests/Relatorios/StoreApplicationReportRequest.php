<?php

namespace App\Http\Requests\Relatorios;

use App\Enums\PermissionCode;
use App\Models\Aplicacao;
use App\Models\User;
use App\Services\Authorization\PortalScope;
use Illuminate\Foundation\Http\FormRequest;

class StoreApplicationReportRequest extends FormRequest
{
    public function authorize(): bool
    {
        $application = $this->route('aplicacao');
        $actor = $this->user();

        return $application instanceof Aplicacao
            && $actor instanceof User
            && $actor->can('view', $application)
            && app(PortalScope::class)->hasAnyPermission($actor, PermissionCode::VIEW_REPORTS);
    }

    public function rules(): array
    {
        return [];
    }
}
