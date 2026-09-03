<?php

namespace Tests\Feature\Dashboard;

use App\Enums\StatusOSEnum;
use App\Models\Cliente;
use App\Models\OrdemServico;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Testa GET /api/v1/dashboard/indicadores (Spec 7 — Req 5).
 */
class DashboardTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::factory()->administrador()->create();
    }

    #[Test]
    public function retorna_contagens_por_status(): void
    {
        $cliente = Cliente::factory()->create(['situacao' => true]);

        OrdemServico::factory()->count(3)->aberta()->create([
            'cliente_id' => $cliente->id,
            'usuario_id' => $this->admin->id,
        ]);
        OrdemServico::factory()->count(2)->emExecucao()->create([
            'cliente_id' => $cliente->id,
            'usuario_id' => $this->admin->id,
        ]);
        OrdemServico::factory()->concluida()->create([
            'cliente_id'      => $cliente->id,
            'usuario_id'      => $this->admin->id,
            'data_fechamento' => now(),
        ]);

        $response = $this->actingAs($this->admin)
            ->getJson('/api/v1/dashboard/indicadores');

        $response->assertOk()
            ->assertJsonPath('por_status.ABERTA', 3)
            ->assertJsonPath('por_status.EM_EXECUCAO', 2)
            ->assertJsonPath('por_status.CONCLUIDA', 1)
            ->assertJsonPath('por_status.CANCELADA', 0);
    }

    #[Test]
    public function retorna_concluidas_no_mes_corrente(): void
    {
        $cliente = Cliente::factory()->create(['situacao' => true]);

        // Concluída no mês corrente
        OrdemServico::factory()->concluida()->create([
            'cliente_id'      => $cliente->id,
            'usuario_id'      => $this->admin->id,
            'data_fechamento' => now(),
        ]);

        // Concluída no mês anterior — não deve contar
        OrdemServico::factory()->concluida()->create([
            'cliente_id'      => $cliente->id,
            'usuario_id'      => $this->admin->id,
            'data_fechamento' => now()->subMonth(),
        ]);

        $this->actingAs($this->admin)
            ->getJson('/api/v1/dashboard/indicadores')
            ->assertOk()
            ->assertJsonPath('concluidas_no_mes', 1);
    }

    #[Test]
    public function sem_token_retorna_401(): void
    {
        $this->getJson('/api/v1/dashboard/indicadores')
            ->assertUnauthorized();
    }
}
