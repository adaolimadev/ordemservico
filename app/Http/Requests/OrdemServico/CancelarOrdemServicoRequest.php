<?php

namespace App\Http\Requests\OrdemServico;

use App\Application\OrdemServico\DTO\CancelarOrdemServicoDTO;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Validação para POST /ordens-servico/{id}/cancelar
 */
class CancelarOrdemServicoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'motivo' => ['required', 'string', 'min:3'],
        ];
    }

    public function messages(): array
    {
        return [
            'motivo.required' => 'O motivo é obrigatório para cancelar a OS.',
            'motivo.min'      => 'O motivo deve ter pelo menos 3 caracteres.',
        ];
    }

    public function toDto(): CancelarOrdemServicoDTO
    {
        return new CancelarOrdemServicoDTO(
            usuarioId: (int) $this->user()->id,
            motivo:    $this->validated('motivo'),
        );
    }
}
