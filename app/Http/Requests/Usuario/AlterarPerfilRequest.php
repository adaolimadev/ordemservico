<?php

namespace App\Http\Requests\Usuario;

use App\Enums\PerfilEnum;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AlterarPerfilRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('gerenciar-usuarios') ?? false;
    }

    public function rules(): array
    {
        return [
            'perfil' => ['required', Rule::enum(PerfilEnum::class)],
        ];
    }

    public function messages(): array
    {
        return [
            'perfil.required' => 'O perfil é obrigatório.',
            'perfil.in'       => 'Perfil inválido. Use ADMINISTRADOR ou ATENDENTE.',
        ];
    }
}
