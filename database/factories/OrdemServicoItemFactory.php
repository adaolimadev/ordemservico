<?php

namespace Database\Factories;

use App\Models\Equipamento;
use App\Models\OrdemServico;
use App\Models\OrdemServicoItem;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<OrdemServicoItem>
 */
class OrdemServicoItemFactory extends Factory
{
    protected $model = OrdemServicoItem::class;

    public function definition(): array
    {
        return [
            'ordem_servico_id' => OrdemServico::factory(),
            'equipamento_id'   => Equipamento::factory(),
        ];
    }
}
