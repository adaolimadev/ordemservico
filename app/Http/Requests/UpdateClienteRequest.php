<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule; // <-- Importação necessária

class UpdateClienteRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; 
    }

    protected function prepareForValidation()
    {
        if ($this->has('cpf_cnpj')) {
            $this->merge([
                'cpf_cnpj' => preg_replace('/[^0-9]/', '', $this->cpf_cnpj),
            ]);
        }
    }

    public function rules(): array
    {
        return [
            'tipo_pessoa' => ['required', 'string', 'in:F,J'],
            'nome_razao_social' => ['required', 'string', 'max:255'],
            'cpf_cnpj' => [
                'required', 
                'string', 
                Rule::unique('clientes', 'cpf_cnpj')->ignore($this->route('cliente'))
            ],
            
            'email' => ['nullable', 'email', 'max:255'],
            'telefone' => ['nullable', 'string', 'max:20'],
            'endereco' => ['nullable', 'string', 'max:255'],
            'situacao' => ['boolean'],
        ];
    }
}