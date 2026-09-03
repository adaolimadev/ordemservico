<?php

namespace Tests\Feature\OrdemServico;

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
 * Testa que historicos_os registra status_anterior e motivo corretamente
 * em todos os cenários (Spec 8 — Req 2).
 */
class HistoricoOsTest extends TestCase
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

    private function criarOsComEquipamento(): OrdemServico
    {
        $tipo  = TipoEquipamento::factory()->create();
        $equip = Equipamento::factory()->create([
            'cliente_id'          => $this->cliente->id,
            'tipo_equipamento_id'  => $tipo->id,
            'situacao'            => true,
        ]);

        $response = $this->actingAs($this->admin)
            ->postJson('/api/v1/ordens-servico', [
                'cliente_id'   => $this->cliente->id,
                'descricao'    => 'Equipamento com defeito',
                'prioridade'   => 'MEDIA',
                'equipamentos' => [$equip->id],
            ]);

        $response->assertSuccessful();

        return OrdemServico::latest()->first();
    }

    // ─── Criação ─────────────────────────────────────────────────────────────

    #[Test]
    public function criacao_grava_historico_com_status_anterior_nulo(): void
    {
        $os = $this->criarOsComEquipamento();

        $this->assertDatabaseHas('historicos_os', [
            'ordem_servico_id' => $os->id,
            'status'           => 'ABERTA',
            'status_anterior'  => null,
        ]);
    }

    // ─── Alteração de status ──────────────────────────────────────────────────

    #[Test]
    public function alterar_status_grava_par_status_anterior_e_novo(): void
    {
        $os = $this->criarOsComEquipamento();

        $this->actingAs($this->admin)
            ->patchJson("/api/v1/ordens-servico/{$os->id}/status", [
                'status' => 'EM_ANALISE',
            ])
            ->assertOk();

        $this->assertDatabaseHas('historicos_os', [
            'ordem_servico_id' => $os->id,
            'status_anterior'  => 'ABERTA',
            'status'           => 'EM_ANALISE',
        ]);
    }

    #[Test]
    public function multiplas_transicoes_gravam_pares_corretos(): void
    {
        $os = $this->criarOsComEquipamento();

        $this->actingAs($this->admin)
            ->patchJson("/api/v1/ordens-servico/{$os->id}/status", ['status' => 'EM_ANALISE'])
            ->assertOk();

        $this->actingAs($this->admin)
            ->patchJson("/api/v1/ordens-servico/{$os->id}/status", ['status' => 'EM_EXECUCAO'])
            ->assertOk();

        // Primeiro histórico: criação (null → ABERTA)
        $this->assertDatabaseHas('historicos_os', [
            'ordem_servico_id' => $os->id,
            'status_anterior'  => null,
            'status'           => 'ABERTA',
        ]);

        // Segundo: ABERTA → EM_ANALISE
        $this->assertDatabaseHas('historicos_os', [
            'ordem_servico_id' => $os->id,
            'status_anterior'  => 'ABERTA',
            'status'           => 'EM_ANALISE',
        ]);

        // Terceiro: EM_ANALISE → EM_EXECUCAO
        $this->assertDatabaseHas('historicos_os', [
            'ordem_servico_id' => $os->id,
            'status_anterior'  => 'EM_ANALISE',
            'status'           => 'EM_EXECUCAO',
        ]);
    }

    // ─── Conclusão ───────────────────────────────────────────────────────────

    #[Test]
    public function concluir_grava_status_anterior_correto(): void
    {
        $os = $this->criarOsComEquipamento();
        $os->update(['status' => StatusOSEnum::EM_EXECUCAO]);

        $this->actingAs($this->admin)
            ->postJson("/api/v1/ordens-servico/{$os->id}/concluir", [
                'diagnostico' => 'Problema resolvido.',
            ])
            ->assertOk();

        $this->assertDatabaseHas('historicos_os', [
            'ordem_servico_id' => $os->id,
            'status_anterior'  => 'EM_EXECUCAO',
            'status'           => 'CONCLUIDA',
        ]);
    }

    // ─── Cancelamento ────────────────────────────────────────────────────────

    #[Test]
    public function cancelar_grava_status_anterior_e_motivo(): void
    {
        $os = $this->criarOsComEquipamento();

        $this->actingAs($this->admin)
            ->postJson("/api/v1/ordens-servico/{$os->id}/cancelar", [
                'motivo' => 'Cliente cancelou o pedido.',
            ])
            ->assertOk();

        $this->assertDatabaseHas('historicos_os', [
            'ordem_servico_id' => $os->id,
            'status_anterior'  => 'ABERTA',
            'status'           => 'CANCELADA',
            'motivo'           => 'Cliente cancelou o pedido.',
        ]);
    }

    // ─── Noop idempotente ────────────────────────────────────────────────────

    #[Test]
    public function mesmo_status_nao_cria_historico_extra(): void
    {
        $os    = $this->criarOsComEquipamento();
        $antes = $os->historicos()->count();

        $this->actingAs($this->admin)
            ->patchJson("/api/v1/ordens-servico/{$os->id}/status", ['status' => 'ABERTA'])
            ->assertOk();

        $this->assertSame($antes, $os->fresh()->historicos()->count());
    }
}
