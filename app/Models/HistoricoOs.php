<?php

namespace App\Models;

use App\Enums\StatusOSEnum;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HistoricoOs extends Model
{
    protected $table = 'historicos_os';

    protected $fillable = [
        'ordem_servico_id',
        'usuario_id',
        'status_anterior',   // null na criação inicial
        'status',            // status atual (após a transição)
        'motivo',            // preenchido no cancelamento
        'data',
    ];

    protected function casts(): array
    {
        return [
            'status_anterior' => StatusOSEnum::class,
            'status'          => StatusOSEnum::class,
            'data'            => 'datetime',
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Relacionamentos
    |--------------------------------------------------------------------------
    */

    public function ordemServico(): BelongsTo
    {
        return $this->belongsTo(OrdemServico::class);
    }

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'usuario_id');
    }
}
