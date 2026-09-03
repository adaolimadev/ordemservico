<?php

namespace App\Http\Requests\Equipamento;

use Illuminate\Foundation\Http\FormRequest;

class EquipamentoIndexRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'cliente_id'   => ['sometimes', 'integer', 'exists:clientes,id'],
            'situacao'     => ['sometimes', 'boolean'],
            'numero_serie' => ['sometimes', 'string', 'max:100'],
            'per_page'     => ['sometimes', 'integer', 'between:1,100'],
        ];
    }
}
