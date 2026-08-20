<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreClienteRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    // Removemos máscaras (pontos e traços) do documento antes de validar
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
            'cpf_cnpj' => ['required', 'string', 'unique:clientes,cpf_cnpj'],
            'email' => [ 'email', 'max:255'],
            'telefone' => [ 'string', 'max:20'],
            'endereco' => [ 'string', 'max:255'],
            'situacao' => ['boolean'],
        ];
    }
}