<?php

namespace App\Exceptions\Domain;

final class EquipamentoComOsAtivaException extends DomainException
{
    public function __construct(int|string $id = '')
    {
        $detalhe = $id ? " (ID: {$id})" : '';
        parent::__construct(
            "O equipamento{$detalhe} já possui uma Ordem de Serviço em andamento."
        );
    }
}
