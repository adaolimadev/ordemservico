<?php

namespace App\Exceptions\Domain;

final class EquipamentoInativoException extends DomainException
{
    public function __construct(int|string $id = '')
    {
        $detalhe = $id ? " (ID: {$id})" : '';
        parent::__construct("O equipamento{$detalhe} está inativo e não pode receber novas Ordens de Serviço.");
    }
}
