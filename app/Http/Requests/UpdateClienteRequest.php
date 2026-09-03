<?php

namespace App\Http\Requests;

use App\Application\Cliente\DTO\AtualizarClienteDTO;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateClienteRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

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
            'cpf_cnpj'          => [
                'required',
                'string',
                Rule::unique('clientes', 'cpf_cnpj')->ignore($this->route('cliente')),
            ],
            'email'    => ['nullable', 'email', 'max:255'],
            'telefone' => ['nullable', 'string', 'max:20'],
            'endereco' => ['nullable', 'string', 'max:255'],
            'situacao' => ['boolean'],
        ];
    }

    public function toDto(): AtualizarClienteDTO
    {
        $data = $this->validated();

        return new AtualizarClienteDTO(
            tipoPessoa:      $data['tipo_pessoa'],
            nomeRazaoSocial: $data['nome_razao_social'],
            cpfCnpj:         $data['cpf_cnpj'],
            email:           $data['email'] ?? null,
            telefone:        $data['telefone'] ?? null,
            endereco:        $data['endereco'] ?? null,
            situacao:        isset($data['situacao']) ? (bool) $data['situacao'] : null,
        );
    }
}
