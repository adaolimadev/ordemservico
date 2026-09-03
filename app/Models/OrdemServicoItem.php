<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrdemServicoItem extends Model
{
    use HasFactory;

    // Informa o nome exato da tabela
    protected $table = 'ordem_servico_itens';

    // Libera os campos para mass assignment (isso resolve o seu erro!)
    protected $fillable = [
        'ordem_servico_id',
        'equipamento_id'
    ];

    public function ordemServico(): BelongsTo
    {
        return $this->belongsTo(OrdemServico::class);
    }

    public function equipamento(): BelongsTo
    {
        return $this->belongsTo(Equipamento::class);
    }
}