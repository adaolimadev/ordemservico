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
 * Testa PATCH /ordens-servico/{id}/status — transições de fluxo (exceto CONCLUIDA/CANCELADA).
 * Cobre RN06–RN09.
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
        $cliente     = Cliente::factory()->create(['situacao' => true]);
        $tipo        = TipoEquipamento::factory()->create();
        $equipamento = Equipamento::factory()->create([
            'cliente_id'          => $cliente->id,
            'tipo_equipamento_id'  => $tipo->id,
            'situacao'            => true,
        ]);

        $this->os = OrdemServico::factory()->create([
            'cliente_id'     => $cliente->id,
            'usuario_id'     => $this->admin->id,
            'status'         => StatusOSEnum::ABERTA,
            'data_abertura'  => now(),
            'data_fechamento' => null,
        ]);

        OrdemServicoItem::factory()->create([
            'ordem_servico_id' => $this->os->id,
            'equipamento_id'   => $equipamento->id,
        ]);

        $this->os->historicos()->create([
            'usuario_id' => $this->admin->id,
            'status'     => StatusOSEnum::ABERTA,
        ]);
    }

    private function patchStatus(string $status, array $extra = []): \Illuminate\Testing\TestResponse
    {
        return $this->actingAs($this->admin)
            ->patchJson("/api/v1/ordens-servico/{$this->os->id}/status", array_merge(['status' => $status], $extra));
    }

    // ─── Transições válidas ──────────────────────────────────────────────────

    #[Test]
    public function transicao_valida_retorna_200_e_grava_historico(): void
    {
        $this->patchStatus('EM_ANALISE')->assertOk();

        $this->assertDatabaseHas('ordens_servico', ['id' => $this->os->id, 'status' => 'EM_ANALISE']);
        $this->assertDatabaseCount('historicos_os', 2);
        $this->assertDatabaseHas('historicos_os', [
            'ordem_servico_id' => $this->os->id,
            'status'           => 'EM_ANALISE',
            'usuario_id'       => $this->admin->id,
        ]);
    }

    #[Test]
    public function em_execucao_para_aguardando_cliente_e_valido(): void
    {
        $this->os->update(['status' => StatusOSEnum::EM_EXECUCAO]);
        $this->patchStatus('AGUARDANDO_CLIENTE')->assertOk();
        $this->assertDatabaseHas('ordens_servico', ['id' => $this->os->id, 'status' => 'AGUARDANDO_CLIENTE']);
    }

    #[Test]
    public function aguardando_cliente_para_em_execucao_e_valido(): void
    {
        $this->os->update(['status' => StatusOSEnum::AGUARDANDO_CLIENTE]);
        $this->patchStatus('EM_EXECUCAO')->assertOk();
        $this->assertDatabaseHas('ordens_servico', ['id' => $this->os->id, 'status' => 'EM_EXECUCAO']);
    }

    // ─── No-op idempotente ───────────────────────────────────────────────────

    #[Test]
    public function mesmo_status_e_noop_sem_novo_historico(): void
    {
        $antes = $this->os->historicos()->count();
        $this->patchStatus('ABERTA')->assertOk();
        $this->assertSame($antes, $this->os->fresh()->historicos()->count());
    }

    // ─── Transições proibidas pela rota ──────────────────────────────────────

    #[Test]
    public function rota_status_rejeita_concluida(): void
    {
        // CONCLUIDA deve ser feita via /concluir
        $this->patchStatus('CONCLUIDA')->assertUnprocessable();
    }

    #[Test]
    public function rota_status_rejeita_cancelada(): void
    {
        // CANCELADA deve ser feita via /cancelar
        $this->patchStatus('CANCELADA')->assertUnprocessable();
    }

    // ─── Transições inválidas pela máquina de estados ────────────────────────

    #[Test]
    public function transicao_invalida_retorna_422_com_code(): void
    {
        // ABERTA não pode ir direto para EM_EXECUCAO
        $this->patchStatus('EM_EXECUCAO')
            ->assertUnprocessable()
            ->assertJsonFragment(['code' => 'TRANSICAO_STATUS_INVALIDA']);
    }

    #[Test]
    public function os_concluida_nao_pode_ser_alterada(): void
    {
        $this->os->update(['status' => StatusOSEnum::CONCLUIDA, 'data_fechamento' => now()]);
        $this->patchStatus('EM_ANALISE')
            ->assertUnprocessable()
            ->assertJsonFragment(['code' => 'TRANSICAO_STATUS_INVALIDA']);
    }

    #[Test]
    public function os_cancelada_nao_pode_entrar_em_execucao(): void
    {
        $this->os->update(['status' => StatusOSEnum::CANCELADA, 'data_fechamento' => now()]);
        $this->patchStatus('EM_EXECUCAO')
            ->assertUnprocessable()
            ->assertJsonFragment(['code' => 'TRANSICAO_STATUS_INVALIDA']);
    }

    // ─── PUT genérico removido (Spec 5) ─────────────────────────────────────

    #[Test]
    public function rota_update_generica_retorna_405(): void
    {
        $this->actingAs($this->admin)
            ->putJson("/api/v1/ordens-servico/{$this->os->id}", ['status' => 'EM_ANALISE'])
            ->assertMethodNotAllowed();
    }

    // ─── Autenticação ────────────────────────────────────────────────────────

    #[Test]
    public function requisicao_sem_token_retorna_401(): void
    {
        $this->patchJson("/api/v1/ordens-servico/{$this->os->id}/status", ['status' => 'EM_ANALISE'])
            ->assertUnauthorized();
    }
}
