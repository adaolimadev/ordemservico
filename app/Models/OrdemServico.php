<?php

namespace App\Models;

use App\Enums\PrioridadeEnum;
use App\Enums\StatusOSEnum;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class OrdemServico extends Model
{
    use HasFactory;

    protected $table = 'ordens_servico';

    protected $fillable = [
        'numero',
        'cliente_id',
        'usuario_id',
        'descricao',
        'diagnostico',
        'prioridade',
        'status',
        'data_abertura',
        'data_fechamento',
    ];

    protected function casts(): array
    {
        return [
            'prioridade'      => PrioridadeEnum::class,
            'status'          => StatusOSEnum::class,
            'data_abertura'   => 'datetime',
            'data_fechamento' => 'datetime',
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Relacionamentos
    |--------------------------------------------------------------------------
    */

    public function cliente(): BelongsTo
    {
        return $this->belongsTo(Cliente::class);
    }

    public function responsavel(): BelongsTo
    {
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

    /*
    |--------------------------------------------------------------------------
    | Query Scopes — Filtros (Spec 7)
    |--------------------------------------------------------------------------
    */

    /**
     * Aplica filtros combinados para a listagem de OS.
     *
     * @param array<string, mixed> $filtros
     */
    public function scopeFiltrar(Builder $query, array $filtros): Builder
    {
        return $query
            ->when($filtros['status']     ?? null, fn ($q, $v) => $q->where('status', $v))
            ->when($filtros['prioridade'] ?? null, fn ($q, $v) => $q->where('prioridade', $v))
            ->when($filtros['cliente_id'] ?? null, fn ($q, $v) => $q->where('cliente_id', $v))
            ->when($filtros['numero']     ?? null, fn ($q, $v) => $q->where('numero', 'like', "%{$v}%"))
            ->when($filtros['aberta_de']  ?? null, fn ($q, $v) => $q->whereDate('data_abertura', '>=', $v))
            ->when($filtros['aberta_ate'] ?? null, fn ($q, $v) => $q->whereDate('data_abertura', '<=', $v));
    }
}