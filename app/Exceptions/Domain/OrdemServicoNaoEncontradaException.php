<?php

namespace App\Exceptions\Domain;

final class OrdemServicoNaoEncontradaException extends DomainException
{
    public function __construct(int|string $id = '')
    {
        $detalhe = $id ? " (ID: {$id})" : '';
        parent::__construct("Ordem de Serviço não encontrada{$detalhe}.");
    }

    public function httpStatus(): int
    {
        return 404;
    }
}
