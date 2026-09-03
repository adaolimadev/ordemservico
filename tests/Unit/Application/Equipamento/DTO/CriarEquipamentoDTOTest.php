<?php

namespace Tests\Unit\Application\Equipamento\DTO;

use App\Application\Equipamento\DTO\CriarEquipamentoDTO;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class CriarEquipamentoDTOTest extends TestCase
{
    #[Test]
    public function instancia_com_propriedades_corretas(): void
    {
        $dto = new CriarEquipamentoDTO(
            clienteId:          1,
            tipoEquipamentoId:  2,
            numeroSerie:        'SN-001',
            marca:              'Dell',
            descricao:          'Notebook i7',
            situacao:           true,
        );

        $this->assertSame(1, $dto->clienteId);
        $this->assertSame(2, $dto->tipoEquipamentoId);
        $this->assertSame('SN-001', $dto->numeroSerie);
        $this->assertSame('Dell', $dto->marca);
        $this->assertSame('Notebook i7', $dto->descricao);
        $this->assertTrue($dto->situacao);
    }

    #[Test]
    public function situacao_padrao_e_true(): void
    {
        $dto = new CriarEquipamentoDTO(1, 2, 'SN-001', 'Dell', 'Notebook');

        $this->assertTrue($dto->situacao);
    }
}
