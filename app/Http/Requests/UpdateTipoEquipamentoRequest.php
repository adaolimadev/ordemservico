<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateTipoEquipamentoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'descricao' => [
                'required', 
                'string', 
                'max:255', 
                // Ignora o ID atual para permitir salvar sem alterar o nome
                Rule::unique('tipos_equipamentos', 'descricao')->ignore($this->route('tipo_equipamento'))
            ],
        ];
    }
}