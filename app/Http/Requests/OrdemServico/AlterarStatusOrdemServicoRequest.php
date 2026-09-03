<?php

namespace App\Http\Requests\OrdemServico;

use App\Application\OrdemServico\DTO\AlterarStatusOrdemServicoDTO;
use App\Enums\StatusOSEnum;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Validação para PATCH /ordens-servico/{id}/status
 *
 * Rejeita explicitamente CONCLUIDA e CANCELADA — para essas ações
 * existem endpoints dedicados /concluir e /cancelar.
 */
class AlterarStatusOrdemServicoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $valoresProibidos = [
            StatusOSEnum::CONCLUIDA->value,
            StatusOSEnum::CANCELADA->value,
        ];

        return [
            'status' => [
                'required',
                Rule::enum(StatusOSEnum::class),
                Rule::notIn($valoresProibidos),
            ],
            'diagnostico' => ['nullable', 'string'],
        ];
    }

    public function messages(): array
    {
        return [
            'status.not_in' => 'Para concluir use POST /concluir. Para cancelar use POST /cancelar.',
        ];
    }

    public function toDto(): AlterarStatusOrdemServicoDTO
    {
        $data = $this->validated();

        return new AlterarStatusOrdemServicoDTO(
            novoStatus:  StatusOSEnum::from($data['status']),
            usuarioId:   (int) $this->user()->id,
            diagnostico: $data['diagnostico'] ?? null,
        );
    }
}
