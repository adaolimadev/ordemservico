<?php

namespace Database\Factories;

use App\Models\Cliente;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Cliente>
 */
class ClienteFactory extends Factory
{
    protected $model = Cliente::class;

    public function definition(): array
    {
        return [
            'tipo_pessoa'       => 'J',
            'nome_razao_social' => fake()->company(),
            'cpf_cnpj'          => fake()->unique()->numerify('##.###.###/####-##'),
            'email'             => fake()->unique()->companyEmail(),
            'telefone'          => fake()->phoneNumber(),
            'endereco'          => fake()->address(),
            'situacao'          => true,
        ];
    }

    public function inativo(): static
    {
        return $this->state(['situacao' => false]);
    }
}
