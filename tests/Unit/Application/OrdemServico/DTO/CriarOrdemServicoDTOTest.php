<?php

namespace Tests\Unit\Application\OrdemServico\DTO;

use App\Application\OrdemServico\DTO\CriarOrdemServicoDTO;
use App\Enums\PrioridadeEnum;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class CriarOrdemServicoDTOTest extends TestCase
{
    #[Test]
    public function instancia_com_propriedades_corretas(): void
    {
        $dto = new CriarOrdemServicoDTO(
            clienteId:      1,
            usuarioId:      2,
            descricao:      'Notebook não liga',
            prioridade:     PrioridadeEnum::ALTA,
            equipamentoIds: [10, 20],
        );

        $this->assertSame(1, $dto->clienteId);
        $this->assertSame(2, $dto->usuarioId);
        $this->assertSame('Notebook não liga', $dto->descricao);
        $this->assertSame(PrioridadeEnum::ALTA, $dto->prioridade);
        $this->assertSame([10, 20], $dto->equipamentoIds);
    }

    #[Test]
    public function e_readonly(): void
    {
        $dto = new CriarOrdemServicoDTO(
            clienteId:      1,
            usuarioId:      1,
            descricao:      'Teste',
            prioridade:     PrioridadeEnum::BAIXA,
            equipamentoIds: [1],
        );

        $this->expectException(\Error::class);

        // @phpstan-ignore-next-line
        $dto->clienteId = 99;
    }

    #[Test]
    public function prioridade_e_enum_tipado(): void
    {
        $dto = new CriarOrdemServicoDTO(1, 1, 'x', PrioridadeEnum::CRITICA, []);

        $this->assertInstanceOf(PrioridadeEnum::class, $dto->prioridade);
        $this->assertSame('CRITICA', $dto->prioridade->value);
    }
}
