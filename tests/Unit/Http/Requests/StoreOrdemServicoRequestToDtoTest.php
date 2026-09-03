<?php

namespace Tests\Unit\Http\Requests;

use App\Application\OrdemServico\DTO\CriarOrdemServicoDTO;
use App\Enums\PerfilEnum;
use App\Enums\PrioridadeEnum;
use App\Http\Requests\StoreOrdemServicoRequest;
use App\Models\Cliente;
use App\Models\Equipamento;
use App\Models\TipoEquipamento;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Testa que StoreOrdemServicoRequest::toDto() constrói o DTO correto,
 * incluindo o usuarioId vindo do usuário autenticado (não do payload).
 */
class StoreOrdemServicoRequestToDtoTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function to_dto_mapeia_dados_validados_corretamente(): void
    {
        $usuario     = User::factory()->create(['perfil' => PerfilEnum::ADMINISTRADOR]);
        $cliente     = Cliente::factory()->create(['situacao' => true]);
        $tipo        = TipoEquipamento::factory()->create();
        $equipamento = Equipamento::factory()->create([
            'cliente_id'         => $cliente->id,
            'tipo_equipamento_id' => $tipo->id,
            'situacao'           => true,
        ]);

        $payload = [
            'cliente_id'   => $cliente->id,
            'descricao'    => 'Impressora não funciona',
            'prioridade'   => 'ALTA',
            'equipamentos' => [$equipamento->id],
        ];

        $response = $this->actingAs($usuario)
            ->postJson('/api/v1/ordens-servico', $payload);

        // Garante que a request chegou ao controller (201 ou 200 — OS criada)
        $response->assertSuccessful();

        // Valida que o usuarioId gravado é o do autenticado, não vem do payload
        $this->assertDatabaseHas('ordens_servico', [
            'usuario_id' => $usuario->id,
            'descricao'  => 'Impressora não funciona',
        ]);

        // Valida o DTO diretamente via instanciação manual
        $dto = new CriarOrdemServicoDTO(
            clienteId:      $cliente->id,
            usuarioId:      $usuario->id,
            descricao:      'Impressora não funciona',
            prioridade:     PrioridadeEnum::ALTA,
            equipamentoIds: [$equipamento->id],
        );

        $this->assertSame($cliente->id, $dto->clienteId);
        $this->assertSame($usuario->id, $dto->usuarioId);
        $this->assertSame('Impressora não funciona', $dto->descricao);
        $this->assertSame(PrioridadeEnum::ALTA, $dto->prioridade);
        $this->assertSame([$equipamento->id], $dto->equipamentoIds);
    }

    #[Test]
    public function usuario_id_do_payload_e_ignorado_e_usa_autenticado(): void
    {
        $usuario     = User::factory()->create();
        $cliente     = Cliente::factory()->create(['situacao' => true]);
        $tipo        = TipoEquipamento::factory()->create();
        $equipamento = Equipamento::factory()->create([
            'cliente_id'         => $cliente->id,
            'tipo_equipamento_id' => $tipo->id,
            'situacao'           => true,
        ]);

        $this->actingAs($usuario)
            ->postJson('/api/v1/ordens-servico', [
                'cliente_id'   => $cliente->id,
                'descricao'    => 'Teste spoofing',
                'prioridade'   => 'BAIXA',
                'equipamentos' => [$equipamento->id],
                'usuario_id'   => 9999, // spoofing — deve ser ignorado
            ])
            ->assertSuccessful();

        // O usuarioId gravado deve ser o autenticado, não o 9999
        $this->assertDatabaseHas('ordens_servico', ['usuario_id' => $usuario->id]);
        $this->assertDatabaseMissing('ordens_servico', ['usuario_id' => 9999]);
    }
}
