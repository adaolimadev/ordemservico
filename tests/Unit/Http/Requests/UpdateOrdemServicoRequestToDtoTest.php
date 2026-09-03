<?php

namespace Tests\Unit\Http\Requests;

use App\Application\OrdemServico\DTO\AlterarStatusOrdemServicoDTO;
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
 * Testa que UpdateOrdemServicoRequest::toDto() constrói o DTO correto,
 * com status como Enum e usuarioId do autenticado.
 */
class UpdateOrdemServicoRequestToDtoTest extends TestCase
{
    use RefreshDatabase;

    private function criarOs(User $usuario): OrdemServico
    {
        $cliente     = Cliente::factory()->create(['situacao' => true]);
        $tipo        = TipoEquipamento::factory()->create();
        $equipamento = Equipamento::factory()->create([
            'cliente_id'         => $cliente->id,
            'tipo_equipamento_id' => $tipo->id,
            'situacao'           => true,
        ]);
        $os = OrdemServico::factory()->create([
            'cliente_id' => $cliente->id,
            'usuario_id' => $usuario->id,
            'status'     => StatusOSEnum::ABERTA,
        ]);
        OrdemServicoItem::factory()->create([
            'ordem_servico_id' => $os->id,
            'equipamento_id'   => $equipamento->id,
        ]);

        return $os;
    }

    #[Test]
    public function to_dto_mapeia_status_como_enum_e_usa_usuario_autenticado(): void
    {
        $usuario = User::factory()->create();
        $os      = $this->criarOs($usuario);

        $response = $this->actingAs($usuario)
            ->patchJson("/api/v1/ordens-servico/{$os->id}", [
                'status'      => 'EM_ANALISE',
                'diagnostico' => 'Verificando cabo',
            ]);

        $response->assertOk();

        // Valida que o status foi gravado e o histórico usa o autenticado
        $this->assertDatabaseHas('ordens_servico', [
            'id'     => $os->id,
            'status' => 'EM_ANALISE',
        ]);
        $this->assertDatabaseHas('historicos_os', [
            'ordem_servico_id' => $os->id,
            'usuario_id'       => $usuario->id,
            'status'           => 'EM_ANALISE',
        ]);
    }

    #[Test]
    public function diagnostico_nulo_nao_afeta_status(): void
    {
        $usuario = User::factory()->create();
        $os      = $this->criarOs($usuario);

        $response = $this->actingAs($usuario)
            ->patchJson("/api/v1/ordens-servico/{$os->id}", [
                'status' => 'EM_ANALISE',
                // sem diagnostico
            ]);

        $response->assertOk();
        $this->assertDatabaseHas('ordens_servico', [
            'id'          => $os->id,
            'status'      => 'EM_ANALISE',
            'diagnostico' => null,
        ]);
    }

    #[Test]
    public function dto_instanciado_diretamente_tem_tipos_corretos(): void
    {
        $dto = new AlterarStatusOrdemServicoDTO(
            novoStatus:  StatusOSEnum::EM_ANALISE,
            usuarioId:   1,
            diagnostico: 'Verificado',
        );

        $this->assertInstanceOf(AlterarStatusOrdemServicoDTO::class, $dto);
        $this->assertSame(StatusOSEnum::EM_ANALISE, $dto->novoStatus);
        $this->assertSame(1, $dto->usuarioId);
        $this->assertSame('Verificado', $dto->diagnostico);
    }
}
