<?php

namespace App\Http\Requests\Usuario;

use App\Enums\PerfilEnum;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class StoreUsuarioRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('gerenciar-usuarios') ?? false;
    }

    public function rules(): array
    {
        return [
            'name'     => ['required', 'string', 'max:255'],
            'email'    => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', Password::min(8)->letters()->numbers()],
            'cargo'    => ['nullable', 'string', 'max:255'],
            'perfil'   => ['required', Rule::enum(PerfilEnum::class)],
            'situacao' => ['boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'email.unique' => 'Este e-mail já está em uso.',
            'perfil.in'    => 'Perfil inválido. Use ADMINISTRADOR ou ATENDENTE.',
        ];
    }
}
