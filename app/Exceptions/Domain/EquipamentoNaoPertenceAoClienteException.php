<?php

namespace App\Exceptions\Domain;

final class EquipamentoNaoPertenceAoClienteException extends DomainException
{
    public function __construct(int $equipamentoId, int $clienteId)
    {
        parent::__construct(
            "O equipamento (ID: {$equipamentoId}) não pertence ao cliente (ID: {$clienteId})."
        );
    }
}
