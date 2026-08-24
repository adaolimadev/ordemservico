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
            'usuario_id' => ['required', 'integer', 'exists:users,id'],
            'status' => ['required', Rule::enum(StatusOSEnum::class)],
            'diagnostico' => ['nullable', 'string'],
        ];
    }
}