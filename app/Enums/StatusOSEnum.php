<?php

namespace App\Enums;

enum StatusOSEnum: string
{
    case ABERTA             = 'ABERTA';
    case EM_ANALISE         = 'EM_ANALISE';
    case EM_EXECUCAO        = 'EM_EXECUCAO';
    case AGUARDANDO_CLIENTE = 'AGUARDANDO_CLIENTE';
    case CONCLUIDA          = 'CONCLUIDA';
    case CANCELADA          = 'CANCELADA';

    /*
    |--------------------------------------------------------------------------
    | Máquina de Estados (RN06 – RN09)
    |--------------------------------------------------------------------------
    | Toda a lógica de transição válida vive aqui, no próprio Enum.
    | Isso permite testar as regras de negócio sem carregar nenhum Model,
    | banco ou framework — apenas PHP puro.
    */

    /**
     * Retorna os status para os quais este status pode transitar.
     *
     * Fluxo principal:
     *   ABERTA → EM_ANALISE → EM_EXECUCAO ⇌ AGUARDANDO_CLIENTE
     *                                    └──→ CONCLUIDA
     * Cancelamento:
     *   ABERTA | EM_ANALISE → CANCELADA
     *
     * @return array<int, self>
     */
    public function transicoesPermitidas(): array
    {
        return match ($this) {
            self::ABERTA             => [self::EM_ANALISE, self::CANCELADA],
            self::EM_ANALISE         => [self::EM_EXECUCAO, self::CANCELADA],
            self::EM_EXECUCAO        => [self::AGUARDANDO_CLIENTE, self::CONCLUIDA],
            self::AGUARDANDO_CLIENTE => [self::EM_EXECUCAO],
            // Estados terminais: sem transições permitidas (RN06, RN07, RN08)
            self::CONCLUIDA,
            self::CANCELADA          => [],
        };
    }

    /**
     * Verifica se é possível transitar para o status destino.
     */
    public function podeTransitarPara(self $destino): bool
    {
        return in_array($destino, $this->transicoesPermitidas(), strict: true);
    }

    /**
     * Indica se este status é terminal (CONCLUIDA ou CANCELADA).
     * Estados terminais preenchem data_fechamento e não aceitam mais mudanças.
     */
    public function ehTerminal(): bool
    {
        return $this === self::CONCLUIDA || $this === self::CANCELADA;
    }

    /**
     * Retorna o label de exibição do status.
     */
    public function label(): string
    {
        return match ($this) {
            self::ABERTA             => 'Aberta',
            self::EM_ANALISE         => 'Em Análise',
            self::EM_EXECUCAO        => 'Em Execução',
            self::AGUARDANDO_CLIENTE => 'Aguardando Cliente',
            self::CONCLUIDA          => 'Concluída',
            self::CANCELADA          => 'Cancelada',
        };
    }
}
