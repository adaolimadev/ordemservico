<?php

namespace Tests\Unit\Application\Cliente\DTO;

use App\Application\Cliente\DTO\CriarClienteDTO;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class CriarClienteDTOTest extends TestCase
{
    #[Test]
    public function instancia_com_propriedades_corretas(): void
    {
        $dto = new CriarClienteDTO(
            tipoPessoa:      'J',
            nomeRazaoSocial: 'Acme Ltda',
            cpfCnpj:         '12345678000195',
            email:           'contato@acme.com',
            telefone:        '11999999999',
            endereco:        'Rua Teste, 1',
            situacao:        true,
        );

        $this->assertSame('J', $dto->tipoPessoa);
        $this->assertSame('Acme Ltda', $dto->nomeRazaoSocial);
        $this->assertSame('12345678000195', $dto->cpfCnpj);
        $this->assertSame('contato@acme.com', $dto->email);
        $this->assertTrue($dto->situacao);
    }

    #[Test]
    public function campos_opcionais_aceitam_null(): void
    {
        $dto = new CriarClienteDTO(
            tipoPessoa:      'F',
            nomeRazaoSocial: 'João Silva',
            cpfCnpj:         '12345678901',
            email:           null,
            telefone:        null,
            endereco:        null,
        );

        $this->assertNull($dto->email);
        $this->assertNull($dto->telefone);
        $this->assertNull($dto->endereco);
    }

    #[Test]
    public function situacao_padrao_e_true(): void
    {
        $dto = new CriarClienteDTO('F', 'Nome', '12345678901', null, null, null);

        $this->assertTrue($dto->situacao);
    }
}
