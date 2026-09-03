<?php

namespace App\Exceptions\Domain;

use Illuminate\Support\Str;

/**
 * Classe base para todas as exceções de domínio da aplicação.
 *
 * Cada exceção concreta herda desta classe e pode sobrescrever
 * httpStatus() e context() para customizar a resposta e o log.
 * O errorCode() é derivado automaticamente do nome da classe.
 */
abstract class DomainException extends \DomainException
{
    /**
     * Código HTTP da resposta (padrão 422 — violação de regra de negócio).
     */
    public function httpStatus(): int
    {
        return 422;
    }

    /**
     * Código de erro legível pela máquina, derivado do nome da classe.
     * Ex: TransicaoStatusInvalidaException → TRANSICAO_STATUS_INVALIDA
     */
    public function errorCode(): string
    {
        $basename = class_basename(static::class);
        $semSufixo = Str::replaceLast('Exception', '', $basename);

        return Str::of($semSufixo)->snake()->upper()->toString();
    }

    /**
     * Contexto adicional para logging (sobrescreva nas subclasses).
     *
     * @return array<string, mixed>
     */
    public function context(): array
    {
        return [];
    }
}
