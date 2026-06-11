<?php

namespace App\Http\Requests\ModelosCartao;

use App\Models\ModeloCartao;
use App\Models\User;
use App\Services\Authorization\ModeloCartaoScope;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Http\FormRequest;

class InactivateModeloCartaoRequest extends FormRequest
{
    public function authorize(): bool
    {
        $actor = $this->user();
        $modelId = $this->modelId();

        if (! $actor instanceof User || $modelId === null) {
            return false;
        }

        $model = app(ModeloCartaoScope::class)
            ->apply(ModeloCartao::query(), $actor)
            ->find($modelId);

        if (! $model instanceof ModeloCartao) {
            throw (new ModelNotFoundException)->setModel(ModeloCartao::class, [$modelId]);
        }

        $this->attributes->set('managed_card_model', $model);

        return $actor->can('delete', $model);
    }

    /** @return array<string, list<mixed>> */
    public function rules(): array
    {
        return [];
    }

    public function cardModel(): ModeloCartao
    {
        /** @var ModeloCartao $model */
        $model = $this->attributes->get('managed_card_model');

        return $model;
    }

    private function modelId(): ?string
    {
        $routeModel = $this->route('modelo');

        return $routeModel instanceof ModeloCartao
            ? $routeModel->id
            : (is_string($routeModel) ? $routeModel : null);
    }
}
