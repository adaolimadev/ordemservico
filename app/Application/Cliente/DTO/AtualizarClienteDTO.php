<?php

namespace App\Application\Cliente\DTO;

/**
 * Dados para atualizar um Cliente existente.
 */
final readonly class AtualizarClienteDTO
{
    public function __construct(
        public string $tipoPessoa,
        public string $nomeRazaoSocial,
        public string $cpfCnpj,
        public ?string $email,
        public ?string $telefone,
        public ?string $endereco,
        public ?bool $situacao,
    ) {}
}
