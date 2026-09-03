<?php

namespace Database\Factories;

use App\Models\TipoEquipamento;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TipoEquipamento>
 */
class TipoEquipamentoFactory extends Factory
{
    protected $model = TipoEquipamento::class;

    public function definition(): array
    {
        return [
            'descricao' => fake()->unique()->word(),
        ];
    }
}
