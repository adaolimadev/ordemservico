<?php

namespace App\Exceptions\Domain;

use App\Enums\StatusOSEnum;

/**
 * Lançada quando uma transição de status viola a máquina de estados da OS.
 * Cobre as regras RN06, RN07, RN08 e RN09 do escopo.
 */
final class TransicaoStatusInvalidaException extends DomainException
{
    public function __construct(
        private readonly StatusOSEnum $atual,
        private readonly StatusOSEnum $destino,
    ) {
        parent::__construct(
            "Transição inválida: {$this->atual->value} → {$this->destino->value}."
        );
    }

    public function context(): array
    {
        return [
            'status_atual'    => $this->atual->value,
            'status_destino'  => $this->destino->value,
        ];
    }
}
