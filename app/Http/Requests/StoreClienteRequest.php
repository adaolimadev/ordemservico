<?php

namespace App\Http\Requests;

use App\Application\Cliente\DTO\CriarClienteDTO;
use Illuminate\Foundation\Http\FormRequest;

class StoreClienteRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    // Remove máscaras (pontos e traços) do documento antes de validar
    protected function prepareForValidation(): void
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
            'tipo_pessoa'       => ['required', 'string', 'in:F,J'],
            'nome_razao_social' => ['required', 'string', 'max:255'],
            'cpf_cnpj'          => ['required', 'string', 'unique:clientes,cpf_cnpj'],
            'email'             => ['nullable', 'email', 'max:255'],
            'telefone'          => ['nullable', 'string', 'max:20'],
            'endereco'          => ['nullable', 'string', 'max:255'],
            'situacao'          => ['boolean'],
        ];
    }

    public function toDto(): CriarClienteDTO
    {
        $data = $this->validated();

        return new CriarClienteDTO(
            tipoPessoa:      $data['tipo_pessoa'],
            nomeRazaoSocial: $data['nome_razao_social'],
            cpfCnpj:         $data['cpf_cnpj'],
            email:           $data['email'] ?? null,
            telefone:        $data['telefone'] ?? null,
            endereco:        $data['endereco'] ?? null,
            situacao:        (bool) ($data['situacao'] ?? true),
        );
    }
}
