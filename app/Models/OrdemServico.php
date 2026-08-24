<?php

namespace App\Models;

use App\Enums\PrioridadeEnum;
use App\Enums\StatusOSEnum;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class OrdemServico extends Model
{
    // Força o Laravel a usar o nome correto da tabela, evitando erros de pluralização
    protected $table = 'ordens_servico'; 

    // Define os campos que podem ser preenchidos em massa (Mass Assignment)
    protected $fillable = [
        'numero', 
        'cliente_id', 
        'usuario_id', 
        'descricao', 
        'diagnostico', 
        'prioridade', 
        'status', 
        'data_abertura', 
        'data_fechamento'
    ];

    /**
     * O método casts() garante a tipagem forte do Laravel 11/12.
     * Ele converte automaticamente os valores do banco de dados para os nossos Enums no PHP.
     */
    protected function casts(): array
    {
        return [
            'prioridade' => PrioridadeEnum::class,
            'status' => StatusOSEnum::class,
            'data_abertura' => 'datetime',
            'data_fechamento' => 'datetime',
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Relacionamentos (Foreign Keys)
    |--------------------------------------------------------------------------
    */

    public function cliente(): BelongsTo
    {
        return $this->belongsTo(Cliente::class);
    }

    public function responsavel(): BelongsTo
    {
        // O segundo parâmetro avisa ao Laravel qual é a coluna correta no banco
        return $this->belongsTo(User::class, 'usuario_id');
    }

    public function itens(): HasMany
    {
        return $this->hasMany(OrdemServicoItem::class);
    }

    public function historicos(): HasMany
    {
        return $this->hasMany(HistoricoOs::class);
    }
}