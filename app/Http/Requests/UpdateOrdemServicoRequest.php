<?php

namespace App\Http\Requests;

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
            // usuario_id NÃO é aceito do payload; vem de Auth::id() no controller (Spec 1 — Req 4)
            'status'      => ['required', Rule::enum(StatusOSEnum::class)],
            'diagnostico' => ['nullable', 'string'],
        ];
    }
}