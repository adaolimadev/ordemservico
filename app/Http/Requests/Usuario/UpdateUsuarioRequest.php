<?php

namespace App\Http\Requests\Usuario;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

class UpdateUsuarioRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('gerenciar-usuarios') ?? false;
    }

    public function rules(): array
    {
        $userId = $this->route('usuario')?->id;

        return [
            'name'     => ['sometimes', 'string', 'max:255'],
            'email'    => ['sometimes', 'string', 'email', 'max:255', "unique:users,email,{$userId}"],
            'password' => ['sometimes', 'string', Password::min(8)->letters()->numbers()],
            'cargo'    => ['sometimes', 'nullable', 'string', 'max:255'],
        ];
    }

    public function messages(): array
    {
        return [
            'email.unique' => 'Este e-mail já está em uso.',
        ];
    }
}
