<?php

namespace App\Http\Requests\Usuario;

use Illuminate\Foundation\Http\FormRequest;

class AlterarSituacaoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('gerenciar-usuarios') ?? false;
    }

    public function rules(): array
    {
        return [
            'situacao' => ['required', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'situacao.required' => 'O campo situacao é obrigatório.',
            'situacao.boolean'  => 'O campo situacao deve ser verdadeiro ou falso.',
        ];
    }
}
