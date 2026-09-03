<?php

namespace App\Http\Requests;

use App\Application\OrdemServico\DTO\CriarOrdemServicoDTO;
use App\Enums\PrioridadeEnum;
use App\Enums\StatusOSEnum;
use Closure;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class StoreOrdemServicoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'cliente_id'   => ['required', 'integer', Rule::exists('clientes', 'id')->where('situacao', true)],
            // usuario_id NÃO é aceito do payload; vem de Auth::id() via toDto() (Spec 1 — Req 4)
            'descricao'    => ['required', 'string'],
            'prioridade'   => ['required', Rule::enum(PrioridadeEnum::class)],

            'equipamentos'   => ['required', 'array', 'min:1'],
            'equipamentos.*' => [
                'required',
                'integer',
                // 1. Valida se pertence ao cliente e está ativo
                Rule::exists('equipamentos', 'id')
                    ->where('cliente_id', $this->cliente_id)
                    ->where('situacao', true),

                // 2. Valida se o equipamento JÁ POSSUI uma OS em andamento (RN10)
                function (string $attribute, mixed $value, Closure $fail) {
                    $osEmAndamento = DB::table('ordem_servico_itens')
                        ->join('ordens_servico', 'ordem_servico_itens.ordem_servico_id', '=', 'ordens_servico.id')
                        ->where('ordem_servico_itens.equipamento_id', $value)
                        ->whereNotIn('ordens_servico.status', [
                            StatusOSEnum::CONCLUIDA->value,
                            StatusOSEnum::CANCELADA->value,
                        ])
                        ->exists();

                    if ($osEmAndamento) {
                        $fail("O equipamento de ID {$value} já possui uma Ordem de Serviço em andamento.");
                    }
                },
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'equipamentos.*.exists' => 'Um ou mais equipamentos selecionados não pertencem a este cliente ou estão inativos.',
        ];
    }

    /**
     * Converte os dados validados em um DTO tipado.
     * O usuarioId vem do usuário autenticado, nunca do payload.
     */
    public function toDto(): CriarOrdemServicoDTO
    {
        $data = $this->validated();

        return new CriarOrdemServicoDTO(
            clienteId:      (int) $data['cliente_id'],
            usuarioId:      (int) $this->user()->id,
            descricao:      $data['descricao'],
            prioridade:     PrioridadeEnum::from($data['prioridade']),
            equipamentoIds: array_map('intval', $data['equipamentos']),
        );
    }
}
