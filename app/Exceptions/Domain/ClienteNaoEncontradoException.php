<?php

namespace App\Exceptions\Domain;

final class ClienteNaoEncontradoException extends DomainException
{
    public function __construct(int|string $id = '')
    {
        $detalhe = $id ? " (ID: {$id})" : '';
        parent::__construct("Cliente não encontrado{$detalhe}.");
    }

    public function httpStatus(): int
    {
        return 404;
    }
}
