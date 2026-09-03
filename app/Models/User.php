<?php

namespace App\Models;

use App\Enums\PerfilEnum;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable, HasApiTokens;

    protected $fillable = [
        'name',
        'email',
        'password',
        'cargo',
        'situacao',
        'perfil',
        'perfil_acesso_id',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password'          => 'hashed',
            'situacao'          => 'boolean',
            'perfil'            => PerfilEnum::class,
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Relacionamentos
    |--------------------------------------------------------------------------
    */

    /**
     * Perfil de acesso via tabela de referência (mantido por compatibilidade).
     * Para autorização, prefira o cast direto em $this->perfil (PerfilEnum).
     */
    public function perfilAcesso(): BelongsTo
    {
        return $this->belongsTo(PerfilUsuario::class, 'perfil_acesso_id');
    }

    /*
    |--------------------------------------------------------------------------
    | Helpers de autorização
    |--------------------------------------------------------------------------
    */

    public function isAdministrador(): bool
    {
        return $this->perfil === PerfilEnum::ADMINISTRADOR;
    }

    public function isAtendente(): bool
    {
        return $this->perfil === PerfilEnum::ATENDENTE;
    }

    public function podeGerenciarUsuarios(): bool
    {
        return $this->perfil?->podeGerenciarUsuarios() ?? false;
    }
}
