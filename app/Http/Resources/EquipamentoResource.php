<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class EquipamentoResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'cliente_id' => $this->cliente_id,
            'tipo_equipamento_id' => $this->tipo_equipamento_id,
            'numero_serie' => $this->numero_serie,
            'marca' => $this->marca,
            'descricao' => $this->descricao,
            'ativo' => $this->situacao,
            'criado_em' => $this->created_at->format('Y-m-d H:i:s'),
            
            // Retorna os dados das relações apenas se elas tiverem sido carregadas na consulta
            'cliente' => new ClienteResource($this->whenLoaded('cliente')),
            'tipo' => $this->whenLoaded('tipoEquipamento', function () {
                return $this->tipoEquipamento->descricao;
            }),
        ];
    }
}