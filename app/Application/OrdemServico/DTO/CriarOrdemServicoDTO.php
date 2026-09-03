<?php

namespace App\Application\OrdemServico\DTO;

use App\Enums\PrioridadeEnum;

/**
 * Dados necessários para abrir uma nova Ordem de Serviço.
 * Imutável por design — sem setters, sem lógica.
 */
final readonly class CriarOrdemServicoDTO
{
    /**
     * @param array<int, int> $equipamentoIds IDs dos equipamentos vinculados à OS
     */
    public function __construct(
        public int $clienteId,
        public int $usuarioId,
        public string $descricao,
        public PrioridadeEnum $prioridade,
        public array $equipamentoIds,
    ) {}
}
