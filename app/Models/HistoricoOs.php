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
        'status',
        'data'
    ];

    protected function casts(): array
    {
        return [
            'status' => StatusOSEnum::class,
            'data' => 'datetime',
        ];
    }

    public function ordemServico(): BelongsTo
    {
        return $this->belongsTo(OrdemServico::class);
    }

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'usuario_id');
    }
}