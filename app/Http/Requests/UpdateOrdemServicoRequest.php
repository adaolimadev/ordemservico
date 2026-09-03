<?php

namespace App\Http\Requests;

use App\Application\OrdemServico\DTO\AlterarStatusOrdemServicoDTO;
use App\Enums\StatusOSEnum;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateOrdemServicoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            // usuario_id NÃO é aceito do payload; vem de Auth::id() via toDto() (Spec 1 — Req 4)
            'status'      => ['required', Rule::enum(StatusOSEnum::class)],
            'diagnostico' => ['nullable', 'string'],
        ];
    }

    /**
     * Converte os dados validados em um DTO tipado.
     * O usuarioId vem do usuário autenticado, nunca do payload.
     */
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
