<?php

namespace App\Models;

use App\Enums\StatusOSEnum;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Equipamento extends Model
{
    use HasFactory;

    protected $fillable = [
        'cliente_id', 'tipo_equipamento_id', 'numero_serie',
        'marca', 'descricao', 'situacao',
    ];

    protected function casts(): array
    {
        return [
            'situacao' => 'boolean',
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

    public function tipoEquipamento(): BelongsTo
    {
        return $this->belongsTo(TipoEquipamento::class);
    }

    /**
     * Itens de OS que referenciam este equipamento.
     */
    public function itensOs(): HasMany
    {
        return $this->hasMany(OrdemServicoItem::class);
    }

    /*
    |--------------------------------------------------------------------------
    | Helpers de domínio
    |--------------------------------------------------------------------------
    */

    /**
     * Verifica se existe ao menos uma OS ativa para este equipamento (Spec 6 — RN02/RN05).
     * OS ativa = qualquer status exceto CONCLUIDA e CANCELADA.
     */
    public function possuiOsAtiva(): bool
    {
        return $this->itensOs()
            ->join('ordens_servico', 'ordem_servico_itens.ordem_servico_id', '=', 'ordens_servico.id')
            ->whereNotIn('ordens_servico.status', [
                StatusOSEnum::CONCLUIDA->value,
                StatusOSEnum::CANCELADA->value,
            ])
            ->exists();
    }
}
