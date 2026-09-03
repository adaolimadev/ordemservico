<?php

namespace App\Exceptions\Domain;

final class OrdemServicoJaConcluidaException extends DomainException
{
    public function __construct()
    {
        parent::__construct('Uma Ordem de Serviço concluída não pode ser alterada.');
    }
}
