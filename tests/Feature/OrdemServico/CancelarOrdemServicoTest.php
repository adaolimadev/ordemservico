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
 * Testa POST /ordens-servico/{id}/cancelar
 */
class CancelarOrdemServicoTest extends TestCase
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
        $this->os = OrdemServico::factory()->aberta()->create([
            'cliente_id' => $cliente->id,
            'usuario_id' => $this->admin->id,
        ]);
        OrdemServicoItem::factory()->create([
            'ordem_servico_id' => $this->os->id,
            'equipamento_id'   => $equip->id,
        ]);
    }

    private function cancelar(array $body = []): \Illuminate\Testing\TestResponse
    {
        return $this->actingAs($this->admin)
            ->postJson("/api/v1/ordens-servico/{$this->os->id}/cancelar", $body);
    }

    #[Test]
    public function cancelar_os_aberta_retorna_200(): void
    {
        $this->cancelar(['motivo' => 'Cliente desistiu do serviço.'])
            ->assertOk()
            ->assertJsonFragment(['message' => 'Ordem de Serviço cancelada com sucesso.']);

        $this->os->refresh();
        $this->assertSame(StatusOSEnum::CANCELADA, $this->os->status);
        $this->assertNotNull($this->os->data_fechamento);
    }

    #[Test]
    public function cancelar_grava_motivo_no_historico(): void
    {
        $this->cancelar(['motivo' => 'Equipamento recuperado pelo cliente.'])->assertOk();

        $this->assertDatabaseHas('historicos_os', [
            'ordem_servico_id' => $this->os->id,
            'status'           => 'CANCELADA',
            'motivo'           => 'Equipamento recuperado pelo cliente.',
            'usuario_id'       => $this->admin->id,
        ]);
    }

    #[Test]
    public function motivo_obrigatorio(): void
    {
        $this->cancelar([])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['motivo']);
    }

    #[Test]
    public function motivo_minimo_3_caracteres(): void
    {
        $this->cancelar(['motivo' => 'AB'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['motivo']);
    }

    #[Test]
    public function nao_pode_cancelar_os_concluida(): void
    {
        $this->os->update(['status' => StatusOSEnum::CONCLUIDA, 'data_fechamento' => now()]);

        $this->cancelar(['motivo' => 'Tentativa inválida.'])
            ->assertUnprocessable()
            ->assertJsonFragment(['code' => 'ORDEM_SERVICO_JA_CONCLUIDA']);
    }

    #[Test]
    public function nao_pode_cancelar_os_ja_cancelada(): void
    {
        $this->os->update(['status' => StatusOSEnum::CANCELADA, 'data_fechamento' => now()]);

        $this->cancelar(['motivo' => 'Duplicata.'])
            ->assertUnprocessable()
            ->assertJsonFragment(['code' => 'ORDEM_SERVICO_JA_CANCELADA']);
    }

    #[Test]
    public function sem_token_retorna_401(): void
    {
        $this->postJson("/api/v1/ordens-servico/{$this->os->id}/cancelar", ['motivo' => 'Teste.'])
            ->assertUnauthorized();
    }
}
