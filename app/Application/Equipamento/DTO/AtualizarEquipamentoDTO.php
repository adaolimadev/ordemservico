<?php

namespace App\Application\Equipamento\DTO;

/**
 * Dados para atualizar um Equipamento existente.
 */
final readonly class AtualizarEquipamentoDTO
{
    public function __construct(
        public int $clienteId,
        public int $tipoEquipamentoId,
        public string $numeroSerie,
        public string $marca,
        public string $descricao,
        public ?bool $situacao,
    ) {}
}
