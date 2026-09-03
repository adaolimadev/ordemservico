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
 * Testa POST /ordens-servico/{id}/concluir
 */
class ConcluirOrdemServicoTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private OrdemServico $os;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::factory()->administrador()->create();
        $cliente     = Cliente::factory()->create(['situacao' => true]);
        $tipo        = TipoEquipamento::factory()->create();
        $equip       = Equipamento::factory()->create([
            'cliente_id'          => $cliente->id,
            'tipo_equipamento_id'  => $tipo->id,
            'situacao'            => true,
        ]);
        $this->os = OrdemServico::factory()->emExecucao()->create([
            'cliente_id' => $cliente->id,
            'usuario_id' => $this->admin->id,
        ]);
        OrdemServicoItem::factory()->create([
            'ordem_servico_id' => $this->os->id,
            'equipamento_id'   => $equip->id,
        ]);
    }

    #[Test]
    public function concluir_em_execucao_retorna_200_e_grava_diagnostico(): void
    {
        $this->actingAs($this->admin)
            ->postJson("/api/v1/ordens-servico/{$this->os->id}/concluir", [
                'diagnostico' => 'Cabo de força substituído com sucesso.',
            ])
            ->assertOk();

        $this->os->refresh();
        $this->assertSame(StatusOSEnum::CONCLUIDA, $this->os->status);
        $this->assertNotNull($this->os->data_fechamento);
        $this->assertSame('Cabo de força substituído com sucesso.', $this->os->diagnostico);
    }

    #[Test]
    public function concluir_grava_historico_com_usuario_autenticado(): void
    {
        $this->actingAs($this->admin)
            ->postJson("/api/v1/ordens-servico/{$this->os->id}/concluir", [
                'diagnostico' => 'Problema resolvido.',
            ])
            ->assertOk();

        $this->assertDatabaseHas('historicos_os', [
            'ordem_servico_id' => $this->os->id,
            'status'           => 'CONCLUIDA',
            'usuario_id'       => $this->admin->id,
        ]);
    }

    #[Test]
    public function diagnostico_obrigatorio(): void
    {
        $this->actingAs($this->admin)
            ->postJson("/api/v1/ordens-servico/{$this->os->id}/concluir", [])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['diagnostico']);
    }

    #[Test]
    public function diagnostico_minimo_3_caracteres(): void
    {
        $this->actingAs($this->admin)
            ->postJson("/api/v1/ordens-servico/{$this->os->id}/concluir", [
                'diagnostico' => 'AB',
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['diagnostico']);
    }

    #[Test]
    public function nao_pode_concluir_os_em_analise(): void
    {
        $this->os->update(['status' => StatusOSEnum::EM_ANALISE]);

        $this->actingAs($this->admin)
            ->postJson("/api/v1/ordens-servico/{$this->os->id}/concluir", [
                'diagnostico' => 'Diagnóstico final.',
            ])
            ->assertUnprocessable()
            ->assertJsonFragment(['code' => 'TRANSICAO_STATUS_INVALIDA']);
    }

    #[Test]
    public function nao_pode_concluir_os_ja_concluida(): void
    {
        $this->os->update(['status' => StatusOSEnum::CONCLUIDA, 'data_fechamento' => now()]);

        $this->actingAs($this->admin)
            ->postJson("/api/v1/ordens-servico/{$this->os->id}/concluir", [
                'diagnostico' => 'Tentativa duplicada.',
            ])
            ->assertUnprocessable()
            ->assertJsonFragment(['code' => 'ORDEM_SERVICO_JA_CONCLUIDA']);
    }
}
