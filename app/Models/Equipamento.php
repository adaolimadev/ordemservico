<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Equipamento extends Model
{
    use HasFactory;

    protected $fillable = [
        'cliente_id', 'tipo_equipamento_id', 'numero_serie', 
        'marca', 'descricao', 'situacao'
    ];

    protected function casts(): array
    {
        return [
            'situacao' => 'boolean',
        ];
    }

    public function cliente(): BelongsTo
    {
        return $this->belongsTo(Cliente::class);
    }

    public function tipoEquipamento(): BelongsTo
    {
        return $this->belongsTo(TipoEquipamento::class);
    }
}