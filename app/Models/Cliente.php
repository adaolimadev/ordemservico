<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Cliente extends Model
{
    use HasFactory;

    protected $fillable = [
        'tipo_pessoa', 'nome_razao_social', 'cpf_cnpj', 
        'email', 'telefone', 'endereco', 'situacao'
    ];

    protected function casts(): array
    {
        return [
            'situacao' => 'boolean', // Converte 0/1 do banco para false/true no PHP
        ];
    }

    public function equipamentos(): HasMany
    {
        return $this->hasMany(Equipamento::class);
    }

    public function ordensServico(): HasMany
    {
        return $this->hasMany(OrdemServico::class);
    }
}