<?php

namespace App\Enums;

enum PerfilEnum: string
{
    case ADMINISTRADOR = 'ADMINISTRADOR';
    case ATENDENTE     = 'ATENDENTE';

    /**
     * Retorna o label de exibição do perfil.
     */
    public function label(): string
    {
        return match ($this) {
            self::ADMINISTRADOR => 'Administrador',
            self::ATENDENTE     => 'Atendente',
        };
    }

    /**
     * Indica se o perfil possui permissão para gerenciar usuários.
     */
    public function podeGerenciarUsuarios(): bool
    {
        return $this === self::ADMINISTRADOR;
    }
}
