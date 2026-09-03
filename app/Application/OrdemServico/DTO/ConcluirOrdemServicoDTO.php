<?php

namespace App\Application\OrdemServico\DTO;

/**
 * Dados para concluir uma OS com diagnóstico final obrigatório.
 */
final readonly class ConcluirOrdemServicoDTO
{
    public function __construct(
        public int $usuarioId,
        public string $diagnostico,
    ) {}
}
