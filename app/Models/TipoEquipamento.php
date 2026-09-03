<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TipoEquipamento extends Model
{
    use HasFactory;

    // Informamos ao Laravel o nome exato da tabela no banco
    protected $table = 'tipos_equipamentos';

    protected $fillable = [
        'descricao',
    ];
}
