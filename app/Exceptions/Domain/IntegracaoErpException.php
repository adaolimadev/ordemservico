<?php

namespace App\Exceptions\Domain;

final class IntegracaoErpException extends DomainException
{
    public function __construct(string $mensagem = 'Falha na integração com o ERP.')
    {
        parent::__construct($mensagem);
    }

    public function httpStatus(): int
    {
        return 502;
    }
}
