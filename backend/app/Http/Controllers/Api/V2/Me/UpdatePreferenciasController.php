<?php

namespace App\Http\Controllers\Api\V2\Me;

use App\Actions\Account\UpdatePreferencesAction;
use App\Http\Controllers\Api\V2\BaseApiController;
use App\Http\Requests\Api\V2\Me\UpdatePreferenciasRequest;
use App\Http\Resources\Api\V2\PreferenciasResource;
use Illuminate\Http\JsonResponse;

class UpdatePreferenciasController extends BaseApiController
{
    public function __construct(
        private UpdatePreferencesAction $action,
    ) {}

    public function __invoke(UpdatePreferenciasRequest $request): JsonResponse
    {
        $user = $request->user();
        $data = $request->validated();
        $atual = $user->preferencia;

        $attributes = [];

        if (array_key_exists('tema', $data)) {
            $attributes = array_merge($attributes, match ($data['tema']) {
                'light' => ['tema' => 'claro', 'contraste_alto' => false, 'tema_sistema' => false],
                'dark' => ['tema' => 'escuro', 'contraste_alto' => false, 'tema_sistema' => false],
                'contrast' => ['tema' => 'escuro', 'contraste_alto' => true, 'tema_sistema' => false],
                'system' => ['tema' => $atual->tema ?? 'claro', 'tema_sistema' => true],
            });
        }

        foreach (['idioma', 'regiao', 'acessibilidade', 'notificacoes'] as $field) {
            if (array_key_exists($field, $data)) {
                $attributes[$field] = $data[$field];
            }
        }

        // Espelha flags de acessibilidade nas colunas booleanas usadas pelo
        // portal web, quando enviadas.
        if (isset($data['acessibilidade']) && is_array($data['acessibilidade'])) {
            foreach (['contraste_alto', 'reduzir_movimento'] as $flag) {
                if (array_key_exists($flag, $data['acessibilidade'])) {
                    $attributes[$flag] = (bool) $data['acessibilidade'][$flag];
                }
            }
        }

        $preferencia = $this->action->execute($user, $attributes);

        return $this->successResponse(PreferenciasResource::make($preferencia));
    }
}
