<?php

namespace App\Application\OrdemServico\DTO;

use App\Enums\StatusOSEnum;

/**
 * Dados para alterar o status de uma OS existente.
 */
final readonly class AlterarStatusOrdemServicoDTO
{
    public function __construct(
        public StatusOSEnum $novoStatus,
        public int $usuarioId,
        public ?string $diagnostico = null,
    ) {}
}
