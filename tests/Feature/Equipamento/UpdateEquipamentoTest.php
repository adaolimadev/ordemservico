<?php

namespace Tests\Feature\Equipamento;

use App\Enums\StatusOSEnum;
use App\Models\Cliente;
use App\Models\Equipamento;
use App\Models\OrdemServico;
use App\Models\OrdemServicoItem;
use App\Models\TipoEquipamento;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Testa regras de integridade no update de equipamento (Spec 6).
 */
class UpdateEquipamentoTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private TipoEquipamento $tipo;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::factory()->administrador()->create();
        $this->tipo  = TipoEquipamento::factory()->create();
    }

    private function payload(Equipamento $e, array $override = []): array
    {
        return array_merge([
            'cliente_id'          => $e->cliente_id,
            'tipo_equipamento_id'  => $e->tipo_equipamento_id,
            'numero_serie'        => $e->numero_serie,
            'marca'               => $e->marca,
            'descricao'           => $e->descricao,
        ], $override);
    }

    // ─── Troca de cliente ────────────────────────────────────────────────────

    #[Test]
    public function nao_permite_trocar_cliente_com_os_ativa(): void
    {
        $clienteA    = Cliente::factory()->create(['situacao' => true]);
        $clienteB    = Cliente::factory()->create(['situacao' => true]);
        $equipamento = Equipamento::factory()->create([
            'cliente_id'          => $clienteA->id,
            'tipo_equipamento_id'  => $this->tipo->id,
            'situacao'            => true,
        ]);

        // Cria OS ativa para o equipamento
        $os = OrdemServico::factory()->aberta()->create([
            'cliente_id' => $clienteA->id,
            'usuario_id' => $this->admin->id,
        ]);
        OrdemServicoItem::factory()->create([
            'ordem_servico_id' => $os->id,
            'equipamento_id'   => $equipamento->id,
        ]);

        $this->actingAs($this->admin)
            ->putJson("/api/v1/equipamentos/{$equipamento->id}", $this->payload($equipamento, [
                'cliente_id' => $clienteB->id,
            ]))
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['cliente_id']);
    }

    #[Test]
    public function permite_trocar_cliente_sem_os_ativa(): void
    {
        $clienteA    = Cliente::factory()->create(['situacao' => true]);
        $clienteB    = Cliente::factory()->create(['situacao' => true]);
        $equipamento = Equipamento::factory()->create([
            'cliente_id'          => $clienteA->id,
            'tipo_equipamento_id'  => $this->tipo->id,
            'situacao'            => true,
        ]);

        // OS encerrada — não deve bloquear
        $os = OrdemServico::factory()->concluida()->create([
            'cliente_id' => $clienteA->id,
            'usuario_id' => $this->admin->id,
        ]);
        OrdemServicoItem::factory()->create([
            'ordem_servico_id' => $os->id,
            'equipamento_id'   => $equipamento->id,
        ]);

        $this->actingAs($this->admin)
            ->putJson("/api/v1/equipamentos/{$equipamento->id}", $this->payload($equipamento, [
                'cliente_id' => $clienteB->id,
            ]))
            ->assertOk();

        $this->assertDatabaseHas('equipamentos', [
            'id'         => $equipamento->id,
            'cliente_id' => $clienteB->id,
        ]);
    }

    // ─── Número de série único (RN03) ────────────────────────────────────────

    #[Test]
    public function numero_serie_unico_ignora_proprio_registro(): void
    {
        $cliente     = Cliente::factory()->create(['situacao' => true]);
        $equipamento = Equipamento::factory()->create([
            'cliente_id'          => $cliente->id,
            'tipo_equipamento_id'  => $this->tipo->id,
            'numero_serie'        => 'SN-UNICO-001',
            'situacao'            => true,
        ]);

        // Enviar o mesmo número de série deve passar
        $this->actingAs($this->admin)
            ->putJson("/api/v1/equipamentos/{$equipamento->id}", $this->payload($equipamento))
            ->assertOk();
    }

    #[Test]
    public function numero_serie_duplicado_retorna_422(): void
    {
        $cliente = Cliente::factory()->create(['situacao' => true]);
        Equipamento::factory()->create([
            'cliente_id'          => $cliente->id,
            'tipo_equipamento_id'  => $this->tipo->id,
            'numero_serie'        => 'SN-JA-EXISTE',
            'situacao'            => true,
        ]);
        $outro = Equipamento::factory()->create([
            'cliente_id'          => $cliente->id,
            'tipo_equipamento_id'  => $this->tipo->id,
            'numero_serie'        => 'SN-OUTRO',
            'situacao'            => true,
        ]);

        $this->actingAs($this->admin)
            ->putJson("/api/v1/equipamentos/{$outro->id}", $this->payload($outro, [
                'numero_serie' => 'SN-JA-EXISTE',
            ]))
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['numero_serie']);
    }
}
