<?php

namespace App\Application\OrdemServico\DTO;

/**
 * Dados para cancelar uma OS existente.
 */
final readonly class CancelarOrdemServicoDTO
{
    public function __construct(
        public int $usuarioId,
        public string $motivo,
    ) {}
}
