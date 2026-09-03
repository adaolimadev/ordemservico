<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OrdemServicoResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'             => $this->id,
            'numero'         => $this->numero,
            'status'         => $this->status?->value,
            'prioridade'     => $this->prioridade?->value,
            'descricao'      => $this->descricao,
            'diagnostico'    => $this->diagnostico,
            'data_abertura'  => $this->data_abertura?->format('Y-m-d H:i:s'),
            'data_fechamento' => $this->data_fechamento?->format('Y-m-d H:i:s'),

            // Relações carregadas sob demanda (whenLoaded evita N+1)
            'cliente'    => new ClienteResource($this->whenLoaded('cliente')),
            'responsavel' => $this->whenLoaded('responsavel', fn () => [
                'id'   => $this->responsavel->id,
                'name' => $this->responsavel->name,
            ]),
            'itens'      => $this->whenLoaded('itens', fn () =>
                $this->itens->map(fn ($item) => [
                    'id'          => $item->id,
                    'equipamento' => $item->relationLoaded('equipamento')
                        ? new EquipamentoResource($item->equipamento)
                        : null,
                ])
            ),
            'historicos' => $this->whenLoaded('historicos', fn () =>
                $this->historicos->map(fn ($h) => [
                    'id'              => $h->id,
                    'status_anterior' => $h->status_anterior?->value,
                    'status'          => $h->status?->value,
                    'motivo'          => $h->motivo,
                    'data'            => $h->data?->format('Y-m-d H:i:s'),
                    'usuario'         => $h->relationLoaded('usuario')
                        ? ['id' => $h->usuario->id, 'name' => $h->usuario->name]
                        : null,
                ])
            ),
        ];
    }
}
