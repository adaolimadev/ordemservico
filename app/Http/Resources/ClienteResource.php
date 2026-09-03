<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ClienteResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'          => $this->id,
            'tipo_pessoa' => $this->tipo_pessoa,
            'nome'        => $this->nome_razao_social,
            'documento'   => $this->cpf_cnpj,
            'contato'     => [
                'email'    => $this->email,
                'telefone' => $this->telefone,
            ],
            'endereco'   => $this->endereco,
            'situacao'   => $this->situacao,    // padronizado para 'situacao' (não 'ativo')
            'criado_em'  => $this->created_at?->format('Y-m-d H:i:s'),
        ];
    }
}
