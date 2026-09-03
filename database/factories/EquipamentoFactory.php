<?php

namespace Database\Factories;

use App\Models\Cliente;
use App\Models\Equipamento;
use App\Models\TipoEquipamento;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Equipamento>
 */
class EquipamentoFactory extends Factory
{
    protected $model = Equipamento::class;

    public function definition(): array
    {
        return [
            'cliente_id'          => Cliente::factory(),
            'tipo_equipamento_id'  => TipoEquipamento::factory(),
            'numero_serie'        => fake()->unique()->bothify('SN-####-???'),
            'marca'               => fake()->randomElement(['Dell', 'HP', 'Lenovo', 'Apple', 'Samsung']),
            'descricao'           => fake()->sentence(),
            'situacao'            => true,
        ];
    }

    public function inativo(): static
    {
        return $this->state(['situacao' => false]);
    }
}
