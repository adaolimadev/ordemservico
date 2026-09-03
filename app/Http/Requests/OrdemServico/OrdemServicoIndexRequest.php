<?php

namespace App\Http\Requests\OrdemServico;

use App\Enums\PrioridadeEnum;
use App\Enums\StatusOSEnum;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Validação para GET /ordens-servico com filtros, ordenação e paginação.
 */
class OrdemServicoIndexRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'status'     => ['sometimes', Rule::enum(StatusOSEnum::class)],
            'prioridade' => ['sometimes', Rule::enum(PrioridadeEnum::class)],
            'cliente_id' => ['sometimes', 'integer', 'exists:clientes,id'],
            'numero'     => ['sometimes', 'string', 'max:50'],
            'aberta_de'  => ['sometimes', 'date'],
            'aberta_ate' => ['sometimes', 'date', 'after_or_equal:aberta_de'],
            'sort'       => ['sometimes', 'string', Rule::in([
                'numero', '-numero',
                'data_abertura', '-data_abertura',
                'data_fechamento', '-data_fechamento',
                'prioridade', '-prioridade',
                'status', '-status',
            ])],
            'per_page' => ['sometimes', 'integer', 'between:1,100'],
        ];
    }

    public function messages(): array
    {
        return [
            'per_page.between' => 'O campo per_page deve ser entre 1 e 100.',
            'sort.in'          => 'Ordenação inválida. Use: numero, data_abertura, prioridade, status (prefixe com - para desc).',
        ];
    }
}
