<?php

namespace App\Http\Requests\OrdemServico;

use App\Application\OrdemServico\DTO\ConcluirOrdemServicoDTO;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Validação para POST /ordens-servico/{id}/concluir
 */
class ConcluirOrdemServicoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'diagnostico' => ['required', 'string', 'min:3'],
        ];
    }

    public function messages(): array
    {
        return [
            'diagnostico.required' => 'O diagnóstico é obrigatório para concluir a OS.',
            'diagnostico.min'      => 'O diagnóstico deve ter pelo menos 3 caracteres.',
        ];
    }

    public function toDto(): ConcluirOrdemServicoDTO
    {
        return new ConcluirOrdemServicoDTO(
            usuarioId:   (int) $this->user()->id,
            diagnostico: $this->validated('diagnostico'),
        );
    }
}
