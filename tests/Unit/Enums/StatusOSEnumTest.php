<?php

namespace Tests\Unit\Enums;

use App\Enums\StatusOSEnum;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * Testa a máquina de estados declarada em StatusOSEnum.
 *
 * Cobre os 36 pares (6x6) da matriz de transições e valida
 * ehTerminal/label — tudo sem banco de dados ou framework.
 */
class StatusOSEnumTest extends TestCase
{
    /*
    |--------------------------------------------------------------------------
    | Matriz de transições esperadas
    |--------------------------------------------------------------------------
    | Formato: [status_atual, status_destino, deve_ser_permitida]
    */

    public static function matrizTransicoes(): array
    {
        $A  = StatusOSEnum::ABERTA;
        $AN = StatusOSEnum::EM_ANALISE;
        $EX = StatusOSEnum::EM_EXECUCAO;
        $AC = StatusOSEnum::AGUARDANDO_CLIENTE;
        $CO = StatusOSEnum::CONCLUIDA;
        $CA = StatusOSEnum::CANCELADA;

        return [
            // ABERTA
            'ABERTA → EM_ANALISE (✓)'           => [$A,  $AN, true],
            'ABERTA → CANCELADA (✓)'             => [$A,  $CA, true],
            'ABERTA → EM_EXECUCAO (✗)'           => [$A,  $EX, false],
            'ABERTA → AGUARDANDO_CLIENTE (✗)'    => [$A,  $AC, false],
            'ABERTA → CONCLUIDA (✗)'             => [$A,  $CO, false],
            'ABERTA → ABERTA (✗)'                => [$A,  $A,  false],

            // EM_ANALISE
            'EM_ANALISE → EM_EXECUCAO (✓)'       => [$AN, $EX, true],
            'EM_ANALISE → CANCELADA (✓)'         => [$AN, $CA, true],
            'EM_ANALISE → ABERTA (✗)'            => [$AN, $A,  false],
            'EM_ANALISE → AGUARDANDO_CLIENTE (✗)'=> [$AN, $AC, false],
            'EM_ANALISE → CONCLUIDA (✗)'         => [$AN, $CO, false],
            'EM_ANALISE → EM_ANALISE (✗)'        => [$AN, $AN, false],

            // EM_EXECUCAO
            'EM_EXECUCAO → AGUARDANDO_CLIENTE (✓)'  => [$EX, $AC, true],
            'EM_EXECUCAO → CONCLUIDA (✓)'            => [$EX, $CO, true],
            'EM_EXECUCAO → ABERTA (✗)'               => [$EX, $A,  false],
            'EM_EXECUCAO → EM_ANALISE (✗)'           => [$EX, $AN, false],
            'EM_EXECUCAO → CANCELADA (✗)'            => [$EX, $CA, false],
            'EM_EXECUCAO → EM_EXECUCAO (✗)'          => [$EX, $EX, false],

            // AGUARDANDO_CLIENTE
            'AGUARDANDO_CLIENTE → EM_EXECUCAO (✓)'       => [$AC, $EX, true],
            'AGUARDANDO_CLIENTE → ABERTA (✗)'             => [$AC, $A,  false],
            'AGUARDANDO_CLIENTE → EM_ANALISE (✗)'         => [$AC, $AN, false],
            'AGUARDANDO_CLIENTE → CONCLUIDA (✗)'          => [$AC, $CO, false],
            'AGUARDANDO_CLIENTE → CANCELADA (✗)'          => [$AC, $CA, false],
            'AGUARDANDO_CLIENTE → AGUARDANDO_CLIENTE (✗)' => [$AC, $AC, false],

            // CONCLUIDA — estado terminal, nenhuma transição permitida (RN06, RN07)
            'CONCLUIDA → ABERTA (✗)'             => [$CO, $A,  false],
            'CONCLUIDA → EM_ANALISE (✗)'         => [$CO, $AN, false],
            'CONCLUIDA → EM_EXECUCAO (✗)'        => [$CO, $EX, false],
            'CONCLUIDA → AGUARDANDO_CLIENTE (✗)' => [$CO, $AC, false],
            'CONCLUIDA → CANCELADA (✗)'          => [$CO, $CA, false],
            'CONCLUIDA → CONCLUIDA (✗)'          => [$CO, $CO, false],

            // CANCELADA — estado terminal, nenhuma transição permitida (RN08)
            'CANCELADA → ABERTA (✗)'             => [$CA, $A,  false],
            'CANCELADA → EM_ANALISE (✗)'         => [$CA, $AN, false],
            'CANCELADA → EM_EXECUCAO (✗)'        => [$CA, $EX, false],
            'CANCELADA → AGUARDANDO_CLIENTE (✗)' => [$CA, $AC, false],
            'CANCELADA → CONCLUIDA (✗)'          => [$CA, $CO, false],
            'CANCELADA → CANCELADA (✗)'          => [$CA, $CA, false],
        ];
    }

    #[Test]
    #[DataProvider('matrizTransicoes')]
    public function verifica_transicao(
        StatusOSEnum $atual,
        StatusOSEnum $destino,
        bool $esperado,
    ): void {
        $this->assertSame(
            $esperado,
            $atual->podeTransitarPara($destino),
            "Esperado podeTransitarPara retornar {$esperado} para {$atual->value} → {$destino->value}",
        );
    }

    #[Test]
    public function concluida_e_terminal(): void
    {
        $this->assertTrue(StatusOSEnum::CONCLUIDA->ehTerminal());
    }

    #[Test]
    public function cancelada_e_terminal(): void
    {
        $this->assertTrue(StatusOSEnum::CANCELADA->ehTerminal());
    }

    #[Test]
    public function status_nao_terminais_nao_sao_terminais(): void
    {
        $naoTerminais = [
            StatusOSEnum::ABERTA,
            StatusOSEnum::EM_ANALISE,
            StatusOSEnum::EM_EXECUCAO,
            StatusOSEnum::AGUARDANDO_CLIENTE,
        ];

        foreach ($naoTerminais as $status) {
            $this->assertFalse(
                $status->ehTerminal(),
                "Status {$status->value} não deveria ser terminal.",
            );
        }
    }

    #[Test]
    public function todos_os_status_possuem_label(): void
    {
        foreach (StatusOSEnum::cases() as $status) {
            $this->assertNotEmpty(
                $status->label(),
                "Status {$status->value} deve ter label.",
            );
        }
    }

    #[Test]
    public function transicoes_permitidas_retornam_instancias_do_proprio_enum(): void
    {
        foreach (StatusOSEnum::cases() as $status) {
            foreach ($status->transicoesPermitidas() as $destino) {
                $this->assertInstanceOf(
                    StatusOSEnum::class,
                    $destino,
                    "transicoesPermitidas() deve retornar instâncias de StatusOSEnum.",
                );
            }
        }
    }
}
