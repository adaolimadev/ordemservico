<?php

namespace App\Http\Requests;

use App\Application\Equipamento\DTO\CriarEquipamentoDTO;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreEquipamentoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            // Valida se o ID existe na tabela clientes E se a coluna situacao é true
            'cliente_id'         => [
                'required',
                'integer',
                Rule::exists('clientes', 'id')->where('situacao', true),
            ],
            'tipo_equipamento_id' => ['required', 'integer', 'exists:tipos_equipamentos,id'],
            'numero_serie'        => ['required', 'string', 'unique:equipamentos,numero_serie'],
            'marca'               => ['required', 'string', 'max:255'],
            'descricao'           => ['required', 'string'],
            'situacao'            => ['boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'cliente_id.exists' => 'O cliente selecionado não existe ou está inativo.',
        ];
    }

    public function toDto(): CriarEquipamentoDTO
    {
        $data = $this->validated();

        return new CriarEquipamentoDTO(
            clienteId:          (int) $data['cliente_id'],
            tipoEquipamentoId:  (int) $data['tipo_equipamento_id'],
            numeroSerie:        $data['numero_serie'],
            marca:              $data['marca'],
            descricao:          $data['descricao'],
            situacao:           (bool) ($data['situacao'] ?? true),
        );
    }
}
