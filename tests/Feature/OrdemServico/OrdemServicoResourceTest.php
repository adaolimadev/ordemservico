<?php

namespace Tests\Feature\OrdemServico;

use App\Models\Cliente;
use App\Models\Equipamento;
use App\Models\OrdemServico;
use App\Models\TipoEquipamento;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Testa o contrato do payload da OrdemServicoResource.
 * Garante que Enums são serializados como string (->value) (Spec 8 — Req 1).
 */
class OrdemServicoResourceTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::factory()->administrador()->create();
    }

    private function criarOs(): OrdemServico
    {
        $cliente = Cliente::factory()->create(['situacao' => true]);
        $tipo    = TipoEquipamento::factory()->create();
        $equip   = Equipamento::factory()->create([
            'cliente_id'          => $cliente->id,
            'tipo_equipamento_id'  => $tipo->id,
            'situacao'            => true,
        ]);

        // Cria via API para garantir histórico inicial via Service
        $response = $this->actingAs($this->admin)
            ->postJson('/api/v1/ordens-servico', [
                'cliente_id'   => $cliente->id,
                'descricao'    => 'Equipamento com defeito',
                'prioridade'   => 'MEDIA',
                'equipamentos' => [$equip->id],
            ]);

        $response->assertSuccessful();

        return OrdemServico::latest()->first();
    }

    #[Test]
    public function status_e_serializado_como_string(): void
    {
        $os = $this->criarOs();

        $this->actingAs($this->admin)
            ->getJson("/api/v1/ordens-servico/{$os->id}")
            ->assertOk()
            ->assertJsonPath('data.status', 'ABERTA');
    }

    #[Test]
    public function prioridade_e_serializada_como_string(): void
    {
        $os = $this->criarOs();

        $this->actingAs($this->admin)
            ->getJson("/api/v1/ordens-servico/{$os->id}")
            ->assertOk()
            ->assertJsonPath('data.prioridade', 'MEDIA');
    }

    #[Test]
    public function historico_expoe_status_anterior_como_string(): void
    {
        $os = $this->criarOs();

        // Realiza uma transição para ter histórico com status_anterior
        $this->actingAs($this->admin)
            ->patchJson("/api/v1/ordens-servico/{$os->id}/status", ['status' => 'EM_ANALISE'])
            ->assertOk();

        $response = $this->actingAs($this->admin)
            ->getJson("/api/v1/ordens-servico/{$os->id}");

        $response->assertOk();

        $historicos = $response->json('data.historicos');
        $this->assertIsArray($historicos);
        $this->assertCount(2, $historicos);

        // Primeiro histórico: criação (status_anterior = null)
        $this->assertNull($historicos[0]['status_anterior']);
        $this->assertSame('ABERTA', $historicos[0]['status']);

        // Segundo histórico: transição ABERTA → EM_ANALISE
        $this->assertSame('ABERTA', $historicos[1]['status_anterior']);
        $this->assertSame('EM_ANALISE', $historicos[1]['status']);
    }

    #[Test]
    public function cliente_situacao_e_boolean_nao_ativo(): void
    {
        $os = $this->criarOs();

        $this->actingAs($this->admin)
            ->getJson("/api/v1/ordens-servico/{$os->id}")
            ->assertOk()
            ->assertJsonPath('data.cliente.situacao', true);
    }

    #[Test]
    public function estrutura_minima_do_payload(): void
    {
        $os = $this->criarOs();

        $this->actingAs($this->admin)
            ->getJson("/api/v1/ordens-servico/{$os->id}")
            ->assertOk()
            ->assertJsonStructure([
                'data' => [
                    'id', 'numero', 'status', 'prioridade',
                    'descricao', 'data_abertura',
                    'cliente', 'historicos',
                ],
            ]);
    }
}
