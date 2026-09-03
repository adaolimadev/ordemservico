<?php

namespace Tests\Feature\OrdemServico;

use App\Enums\PerfilEnum;
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
 * Testa os endpoints de atualização de status e cancelamento da OS.
 * Cobre as regras RN06, RN07, RN08 e RN09.
 */
class AlterarStatusTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private OrdemServico $os;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->administrador()->create();

        $cliente    = Cliente::factory()->create(['situacao' => true]);
        $tipo       = TipoEquipamento::factory()->create();
        $equipamento = Equipamento::factory()->create([
            'cliente_id'          => $cliente->id,
            'tipo_equipamento_id'  => $tipo->id,
            'situacao'            => true,
        ]);

        $this->os = OrdemServico::factory()->create([
            'cliente_id'    => $cliente->id,
            'usuario_id'    => $this->admin->id,
            'status'        => StatusOSEnum::ABERTA,
            'data_abertura' => now(),
            'data_fechamento' => null,
        ]);

        OrdemServicoItem::factory()->create([
            'ordem_servico_id' => $this->os->id,
            'equipamento_id'   => $equipamento->id,
        ]);

        // Histórico inicial
        $this->os->historicos()->create([
            'usuario_id' => $this->admin->id,
            'status'     => StatusOSEnum::ABERTA,
        ]);
    }

    // ─── Transições válidas ──────────────────────────────────────────────────

    #[Test]
    public function transicao_valida_retorna_200_e_grava_historico(): void
    {
        $response = $this->actingAs($this->admin)
            ->patchJson("/api/v1/ordens-servico/{$this->os->id}", [
                'status' => 'EM_ANALISE',
            ]);

        $response->assertOk();

        $this->assertDatabaseHas('ordens_servico', [
            'id'     => $this->os->id,
            'status' => 'EM_ANALISE',
        ]);

        // Deve ter dois registros no histórico: ABERTA e EM_ANALISE
        $this->assertDatabaseCount('historicos_os', 2);
        $this->assertDatabaseHas('historicos_os', [
            'ordem_servico_id' => $this->os->id,
            'status'           => 'EM_ANALISE',
            'usuario_id'       => $this->admin->id,
        ]);
    }

    #[Test]
    public function transicao_em_execucao_para_aguardando_cliente_e_valida(): void
    {
        $this->os->update(['status' => StatusOSEnum::EM_EXECUCAO]);

        $response = $this->actingAs($this->admin)
            ->patchJson("/api/v1/ordens-servico/{$this->os->id}", [
                'status' => 'AGUARDANDO_CLIENTE',
            ]);

        $response->assertOk();
        $this->assertDatabaseHas('ordens_servico', [
            'id'     => $this->os->id,
            'status' => 'AGUARDANDO_CLIENTE',
        ]);
    }

    #[Test]
    public function transicao_aguardando_cliente_para_em_execucao_e_valida(): void
    {
        $this->os->update(['status' => StatusOSEnum::AGUARDANDO_CLIENTE]);

        $response = $this->actingAs($this->admin)
            ->patchJson("/api/v1/ordens-servico/{$this->os->id}", [
                'status' => 'EM_EXECUCAO',
            ]);

        $response->assertOk();
        $this->assertDatabaseHas('ordens_servico', [
            'id'     => $this->os->id,
            'status' => 'EM_EXECUCAO',
        ]);
    }

    // ─── Transição para terminal: data_fechamento ────────────────────────────

    #[Test]
    public function concluir_os_preenche_data_fechamento(): void
    {
        $this->os->update(['status' => StatusOSEnum::EM_EXECUCAO]);

        $response = $this->actingAs($this->admin)
            ->patchJson("/api/v1/ordens-servico/{$this->os->id}", [
                'status' => 'CONCLUIDA',
            ]);

        $response->assertOk();

        $this->os->refresh();
        $this->assertNotNull($this->os->data_fechamento);
        $this->assertEquals(StatusOSEnum::CONCLUIDA, $this->os->status);
    }

    // ─── No-op idempotente ───────────────────────────────────────────────────

    #[Test]
    public function mesmo_status_e_noop_sem_novo_historico(): void
    {
        $historicoAntes = $this->os->historicos()->count();

        $response = $this->actingAs($this->admin)
            ->patchJson("/api/v1/ordens-servico/{$this->os->id}", [
                'status' => 'ABERTA', // mesmo status atual
            ]);

        $response->assertOk();

        // Histórico não deve ter crescido
        $this->assertEquals($historicoAntes, $this->os->fresh()->historicos()->count());
    }

    // ─── Transições inválidas (422) ──────────────────────────────────────────

    #[Test]
    public function transicao_invalida_retorna_422_com_code(): void
    {
        // ABERTA → CONCLUIDA: transição inválida
        $response = $this->actingAs($this->admin)
            ->patchJson("/api/v1/ordens-servico/{$this->os->id}", [
                'status' => 'CONCLUIDA',
            ]);

        $response->assertUnprocessable()
            ->assertJsonFragment(['code' => 'TRANSICAO_STATUS_INVALIDA']);
    }

    #[Test]
    public function os_concluida_nao_pode_ser_alterada(): void
    {
        $this->os->update([
            'status'          => StatusOSEnum::CONCLUIDA,
            'data_fechamento' => now(),
        ]);

        $response = $this->actingAs($this->admin)
            ->patchJson("/api/v1/ordens-servico/{$this->os->id}", [
                'status' => 'ABERTA',
            ]);

        $response->assertUnprocessable()
            ->assertJsonFragment(['code' => 'TRANSICAO_STATUS_INVALIDA']);
    }

    #[Test]
    public function os_cancelada_nao_pode_entrar_em_execucao(): void
    {
        $this->os->update([
            'status'          => StatusOSEnum::CANCELADA,
            'data_fechamento' => now(),
        ]);

        $response = $this->actingAs($this->admin)
            ->patchJson("/api/v1/ordens-servico/{$this->os->id}", [
                'status' => 'EM_EXECUCAO',
            ]);

        $response->assertUnprocessable()
            ->assertJsonFragment(['code' => 'TRANSICAO_STATUS_INVALIDA']);
    }

    // ─── Cancelamento ────────────────────────────────────────────────────────

    #[Test]
    public function cancelar_os_aberta_retorna_200(): void
    {
        $response = $this->actingAs($this->admin)
            ->postJson("/api/v1/ordens-servico/{$this->os->id}/cancelar");

        $response->assertOk()
            ->assertJsonFragment(['message' => 'Ordem de Serviço cancelada com sucesso.']);

        $this->os->refresh();
        $this->assertEquals(StatusOSEnum::CANCELADA, $this->os->status);
        $this->assertNotNull($this->os->data_fechamento);
    }

    #[Test]
    public function cancelar_os_concluida_retorna_422(): void
    {
        $this->os->update([
            'status'          => StatusOSEnum::CONCLUIDA,
            'data_fechamento' => now(),
        ]);

        $response = $this->actingAs($this->admin)
            ->postJson("/api/v1/ordens-servico/{$this->os->id}/cancelar");

        $response->assertUnprocessable()
            ->assertJsonFragment(['code' => 'ORDEM_SERVICO_JA_CONCLUIDA']);
    }

    #[Test]
    public function cancelar_os_ja_cancelada_retorna_422(): void
    {
        $this->os->update([
            'status'          => StatusOSEnum::CANCELADA,
            'data_fechamento' => now(),
        ]);

        $response = $this->actingAs($this->admin)
            ->postJson("/api/v1/ordens-servico/{$this->os->id}/cancelar");

        $response->assertUnprocessable()
            ->assertJsonFragment(['code' => 'ORDEM_SERVICO_JA_CANCELADA']);
    }

    // ─── Autenticação ────────────────────────────────────────────────────────

    #[Test]
    public function requisicao_sem_token_retorna_401(): void
    {
        $this->patchJson("/api/v1/ordens-servico/{$this->os->id}", [
            'status' => 'EM_ANALISE',
        ])->assertUnauthorized();
    }
}
