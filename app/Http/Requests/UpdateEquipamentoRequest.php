<?php

namespace App\Http\Requests;

use App\Application\Equipamento\DTO\AtualizarEquipamentoDTO;
use Closure;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateEquipamentoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        /** @var \App\Models\Equipamento $equipamento */
        $equipamento = $this->route('equipamento');

        return [
            'cliente_id' => [
                'required',
                'integer',
                Rule::exists('clientes', 'id')->where('situacao', true),
                // Bloqueia troca de cliente se o equipamento possui OS ativa (Spec 6 — RN02/RN05)
                function (string $attribute, mixed $value, Closure $fail) use ($equipamento) {
                    if (
                        (int) $value !== (int) $equipamento->cliente_id
                        && $equipamento->possuiOsAtiva()
                    ) {
                        $fail('Não é possível trocar o cliente de um equipamento com Ordem de Serviço ativa.');
                    }
                },
            ],
            'tipo_equipamento_id' => ['required', 'integer', 'exists:tipos_equipamentos,id'],
            // Ignora o próprio registro na validação de unicidade (RN03)
            'numero_serie' => [
                'required',
                'string',
                Rule::unique('equipamentos', 'numero_serie')->ignore($equipamento->id),
            ],
            'marca'    => ['required', 'string', 'max:255'],
            'descricao' => ['required', 'string'],
            'situacao'  => ['boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'cliente_id.exists'   => 'O cliente selecionado não existe ou está inativo.',
            'numero_serie.unique' => 'Este número de série já está cadastrado em outro equipamento no sistema.',
        ];
    }

    public function toDto(): AtualizarEquipamentoDTO
    {
        $data = $this->validated();

        return new AtualizarEquipamentoDTO(
            clienteId:         (int) $data['cliente_id'],
            tipoEquipamentoId: (int) $data['tipo_equipamento_id'],
            numeroSerie:       $data['numero_serie'],
            marca:             $data['marca'],
            descricao:         $data['descricao'],
            situacao:          isset($data['situacao']) ? (bool) $data['situacao'] : null,
        );
    }
}
