<?php

namespace Tests\Unit\Application\OrdemServico\DTO;

use App\Application\OrdemServico\DTO\AlterarStatusOrdemServicoDTO;
use App\Enums\StatusOSEnum;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class AlterarStatusOrdemServicoDTOTest extends TestCase
{
    #[Test]
    public function instancia_com_valores_corretos(): void
    {
        $dto = new AlterarStatusOrdemServicoDTO(
            novoStatus:  StatusOSEnum::EM_ANALISE,
            usuarioId:   3,
            diagnostico: 'Verificado cabo de energia',
        );

        $this->assertSame(StatusOSEnum::EM_ANALISE, $dto->novoStatus);
        $this->assertSame(3, $dto->usuarioId);
        $this->assertSame('Verificado cabo de energia', $dto->diagnostico);
    }

    #[Test]
    public function diagnostico_e_opcional_e_nulo_por_padrao(): void
    {
        $dto = new AlterarStatusOrdemServicoDTO(
            novoStatus: StatusOSEnum::EM_EXECUCAO,
            usuarioId:  1,
        );

        $this->assertNull($dto->diagnostico);
    }

    #[Test]
    public function novo_status_e_enum_tipado(): void
    {
        $dto = new AlterarStatusOrdemServicoDTO(StatusOSEnum::CONCLUIDA, 1);

        $this->assertInstanceOf(StatusOSEnum::class, $dto->novoStatus);
    }
}
