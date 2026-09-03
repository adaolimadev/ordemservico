<?php

namespace App\Exceptions\Domain;

final class OrdemServicoJaCanceladaException extends DomainException
{
    public function __construct()
    {
        parent::__construct('Esta Ordem de Serviço já se encontra cancelada.');
    }
}
