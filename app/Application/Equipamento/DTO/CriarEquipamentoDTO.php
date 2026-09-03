<?php

namespace App\Application\Equipamento\DTO;

/**
 * Dados para criar um novo Equipamento.
 */
final readonly class CriarEquipamentoDTO
{
    public function __construct(
        public int $clienteId,
        public int $tipoEquipamentoId,
        public string $numeroSerie,
        public string $marca,
        public string $descricao,
        public bool $situacao = true,
    ) {}
}
