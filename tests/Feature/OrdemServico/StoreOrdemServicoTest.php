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
 * Testes de regressão para criação de OS (Spec 6 — RN04, RN05, RN10).
 */
class StoreOrdemServicoTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private Cliente $cliente;
    private TipoEquipamento $tipo;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin   = User::factory()->administrador()->create();
        $this->cliente = Cliente::factory()->create(['situacao' => true]);
        $this->tipo    = TipoEquipamento::factory()->create();
    }

    #[Test]
    public function rejeita_equipamento_de_outro_cliente(): void
    {
        $outroCliente = Cliente::factory()->create(['situacao' => true]);
        $equipamento  = Equipamento::factory()->create([
            'cliente_id'          => $outroCliente->id,
            'tipo_equipamento_id'  => $this->tipo->id,
            'situacao'            => true,
        ]);

        $this->actingAs($this->admin)
            ->postJson('/api/v1/ordens-servico', [
                'cliente_id'   => $this->cliente->id,
                'descricao'    => 'Teste',
                'prioridade'   => 'MEDIA',
                'equipamentos' => [$equipamento->id], // pertence a outro cliente
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['equipamentos.0']);
    }

    #[Test]
    public function rejeita_equipamento_inativo(): void
    {
        $equipamento = Equipamento::factory()->create([
            'cliente_id'          => $this->cliente->id,
            'tipo_equipamento_id'  => $this->tipo->id,
            'situacao'            => false, // inativo
        ]);

        $this->actingAs($this->admin)
            ->postJson('/api/v1/ordens-servico', [
                'cliente_id'   => $this->cliente->id,
                'descricao'    => 'Teste',
                'prioridade'   => 'MEDIA',
                'equipamentos' => [$equipamento->id],
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['equipamentos.0']);
    }

    #[Test]
    public function rejeita_equipamento_com_os_em_andamento(): void
    {
        $equipamento = Equipamento::factory()->create([
            'cliente_id'          => $this->cliente->id,
            'tipo_equipamento_id'  => $this->tipo->id,
            'situacao'            => true,
        ]);

        $os = OrdemServico::factory()->aberta()->create([
            'cliente_id' => $this->cliente->id,
            'usuario_id' => $this->admin->id,
        ]);
        OrdemServicoItem::factory()->create([
            'ordem_servico_id' => $os->id,
            'equipamento_id'   => $equipamento->id,
        ]);

        $this->actingAs($this->admin)
            ->postJson('/api/v1/ordens-servico', [
                'cliente_id'   => $this->cliente->id,
                'descricao'    => 'Segunda OS',
                'prioridade'   => 'ALTA',
                'equipamentos' => [$equipamento->id],
            ])
            ->assertUnprocessable();
    }

    #[Test]
    public function data_abertura_preenchida_automaticamente(): void
    {
        $equipamento = Equipamento::factory()->create([
            'cliente_id'          => $this->cliente->id,
            'tipo_equipamento_id'  => $this->tipo->id,
            'situacao'            => true,
        ]);

        $this->actingAs($this->admin)
            ->postJson('/api/v1/ordens-servico', [
                'cliente_id'   => $this->cliente->id,
                'descricao'    => 'Teste data abertura',
                'prioridade'   => 'BAIXA',
                'equipamentos' => [$equipamento->id],
            ])
            ->assertSuccessful();

        $os = OrdemServico::latest()->first();
        $this->assertNotNull($os->data_abertura);
        $this->assertEquals(StatusOSEnum::ABERTA, $os->status);
    }
}
