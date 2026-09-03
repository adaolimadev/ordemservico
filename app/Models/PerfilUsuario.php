<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PerfilUsuario extends Model
{
    protected $table = 'perfis_usuarios';

    protected $fillable = [
        'descricao',
    ];

    /*
    |--------------------------------------------------------------------------
    | Relacionamentos
    |--------------------------------------------------------------------------
    */

    public function usuarios(): HasMany
    {
        return $this->hasMany(User::class, 'perfil_acesso_id');
    }
}
