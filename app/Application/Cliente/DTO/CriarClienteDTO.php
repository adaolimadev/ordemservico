<?php

namespace App\Application\Cliente\DTO;

/**
 * Dados para criar um novo Cliente.
 */
final readonly class CriarClienteDTO
{
    public function __construct(
        public string $tipoPessoa,
        public string $nomeRazaoSocial,
        public string $cpfCnpj,
        public ?string $email,
        public ?string $telefone,
        public ?string $endereco,
        public bool $situacao = true,
    ) {}
}
