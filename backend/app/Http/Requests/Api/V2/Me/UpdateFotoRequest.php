<?php

namespace App\Http\Requests\Api\V2\Me;

use Illuminate\Foundation\Http\FormRequest;

class UpdateFotoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /** @return array<string, list<string>> */
    public function rules(): array
    {
        return [
            'foto' => ['required', 'image', 'mimes:jpeg,png,webp', 'max:5120'],
        ];
    }
}
