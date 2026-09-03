<?php

namespace Tests\Feature\OrdemServico;

use App\Enums\PrioridadeEnum;
use App\Enums\StatusOSEnum;
use App\Models\Cliente;
use App\Models\OrdemServico;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Testa os filtros, paginação e ordenação da listagem de OS (Spec 7).
 */
class OrdemServicoFiltroTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private Cliente $cliente;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin   = User::factory()->administrador()->create();
        $this->cliente = Cliente::factory()->create(['situacao' => true]);
    }

    private function criarOs(array $attrs = []): OrdemServico
    {
        return OrdemServico::factory()->create(array_merge([
            'cliente_id' => $this->cliente->id,
            'usuario_id' => $this->admin->id,
        ], $attrs));
    }

    #[Test]
    public function filtra_por_status(): void
    {
        $this->criarOs(['status' => StatusOSEnum::ABERTA]);
        $this->criarOs(['status' => StatusOSEnum::EM_ANALISE]);

        $response = $this->actingAs($this->admin)
            ->getJson('/api/v1/ordens-servico?status=ABERTA');

        $response->assertOk()
            ->assertJsonPath('meta.total', 1)
            ->assertJsonPath('data.0.status', 'ABERTA');
    }

    #[Test]
    public function filtra_por_prioridade(): void
    {
        $this->criarOs(['prioridade' => PrioridadeEnum::ALTA]);
        $this->criarOs(['prioridade' => PrioridadeEnum::BAIXA]);

        $this->actingAs($this->admin)
            ->getJson('/api/v1/ordens-servico?prioridade=ALTA')
            ->assertOk()
            ->assertJsonPath('meta.total', 1);
    }

    #[Test]
    public function filtra_por_cliente(): void
    {
        $outroCliente = Cliente::factory()->create(['situacao' => true]);
        $this->criarOs();
        OrdemServico::factory()->create([
            'cliente_id' => $outroCliente->id,
            'usuario_id' => $this->admin->id,
        ]);

        $this->actingAs($this->admin)
            ->getJson("/api/v1/ordens-servico?cliente_id={$this->cliente->id}")
            ->assertOk()
            ->assertJsonPath('meta.total', 1);
    }

    #[Test]
    public function per_page_fora_do_limite_retorna_422(): void
    {
        $this->actingAs($this->admin)
            ->getJson('/api/v1/ordens-servico?per_page=200')
            ->assertUnprocessable();

        $this->actingAs($this->admin)
            ->getJson('/api/v1/ordens-servico?per_page=0')
            ->assertUnprocessable();
    }

    #[Test]
    public function per_page_configuravel(): void
    {
        $this->criarOs();
        $this->criarOs();
        $this->criarOs();

        $this->actingAs($this->admin)
            ->getJson('/api/v1/ordens-servico?per_page=2')
            ->assertOk()
            ->assertJsonPath('meta.per_page', 2)
            ->assertJsonCount(2, 'data');
    }

    #[Test]
    public function sort_invalido_retorna_422(): void
    {
        $this->actingAs($this->admin)
            ->getJson('/api/v1/ordens-servico?sort=campo_invalido')
            ->assertUnprocessable();
    }

    #[Test]
    public function sem_token_retorna_401(): void
    {
        $this->getJson('/api/v1/ordens-servico')
            ->assertUnauthorized();
    }
}
