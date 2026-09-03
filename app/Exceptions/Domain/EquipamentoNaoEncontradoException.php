<?php

namespace App\Exceptions\Domain;

final class EquipamentoNaoEncontradoException extends DomainException
{
    public function __construct(int|string $id = '')
    {
        $detalhe = $id ? " (ID: {$id})" : '';
        parent::__construct("Equipamento não encontrado{$detalhe}.");
    }

    public function httpStatus(): int
    {
        return 404;
    }
}
