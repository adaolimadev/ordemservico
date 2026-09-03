<?php

namespace App\Http\Requests\Cliente;

use Illuminate\Foundation\Http\FormRequest;

class ClienteIndexRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'situacao'  => ['sometimes', 'boolean'],
            'search'    => ['sometimes', 'string', 'max:100'],
            'per_page'  => ['sometimes', 'integer', 'between:1,100'],
        ];
    }
}
