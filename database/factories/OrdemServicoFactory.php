<?php

namespace Database\Factories;

use App\Enums\PrioridadeEnum;
use App\Enums\StatusOSEnum;
use App\Models\Cliente;
use App\Models\OrdemServico;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<OrdemServico>
 */
class OrdemServicoFactory extends Factory
{
    protected $model = OrdemServico::class;

    private static int $contador = 0;

    public function definition(): array
    {
        self::$contador++;
        $numero = 'OS-' . now()->format('Ymd') . '-' . str_pad(self::$contador, 4, '0', STR_PAD_LEFT);

        return [
            'numero'          => $numero,
            'cliente_id'      => Cliente::factory(),
            'usuario_id'      => User::factory(),
            'descricao'       => fake()->paragraph(),
            'diagnostico'     => null,
            'prioridade'      => PrioridadeEnum::MEDIA,
            'status'          => StatusOSEnum::ABERTA,
            'data_abertura'   => now(),
            'data_fechamento' => null,
        ];
    }

    public function aberta(): static
    {
        return $this->state([
            'status'          => StatusOSEnum::ABERTA,
            'data_fechamento' => null,
        ]);
    }

    public function emAnalise(): static
    {
        return $this->state([
            'status'          => StatusOSEnum::EM_ANALISE,
            'data_fechamento' => null,
        ]);
    }

    public function emExecucao(): static
    {
        return $this->state([
            'status'          => StatusOSEnum::EM_EXECUCAO,
            'data_fechamento' => null,
        ]);
    }

    public function concluida(): static
    {
        return $this->state([
            'status'          => StatusOSEnum::CONCLUIDA,
            'data_fechamento' => now(),
        ]);
    }

    public function cancelada(): static
    {
        return $this->state([
            'status'          => StatusOSEnum::CANCELADA,
            'data_fechamento' => now(),
        ]);
    }
}
