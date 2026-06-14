<?php

namespace App\Http\Requests\Api\V2\Me;

use Closure;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

class UpdatePasswordRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'senha_atual' => [
                'required',
                'string',
                function (string $attribute, mixed $value, Closure $fail): void {
                    if (! Hash::check((string) $value, (string) $this->user()->password)) {
                        $fail('A senha atual informada esta incorreta.');
                    }
                },
            ],
            'senha' => ['required', 'string', Password::min(8)],
        ];
    }
}
