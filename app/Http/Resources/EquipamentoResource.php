<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class EquipamentoResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'           => $this->id,
            'numero_serie' => $this->numero_serie,
            'marca'        => $this->marca,
            'descricao'    => $this->descricao,
            'situacao'     => $this->situacao,   // padronizado para 'situacao' (não 'ativo')
            'criado_em'    => $this->created_at?->format('Y-m-d H:i:s'),

            // Relações carregadas sob demanda
            'cliente' => new ClienteResource($this->whenLoaded('cliente')),
            'tipo'    => $this->whenLoaded('tipoEquipamento', fn () => [
                'id'       => $this->tipoEquipamento->id,
                'descricao' => $this->tipoEquipamento->descricao,
            ]),
        ];
    }
}
