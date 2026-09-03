# Plano de Implementação — DTOs entre HTTP e Camada de Aplicação

- [ ] 1. Criar estrutura `app/Application/<Modulo>/DTO/`
  - Namespaces `App\Application\OrdemServico\DTO`, `App\Application\Cliente\DTO`, `App\Application\Equipamento\DTO`
  - _Requisitos: 1.1_

- [ ] 2. Implementar DTOs iniciais como `final readonly`
  - `CriarOrdemServicoDTO`
  - `AlterarStatusOrdemServicoDTO`
  - `CancelarOrdemServicoDTO`
  - `CriarClienteDTO`, `AtualizarClienteDTO`
  - `CriarEquipamentoDTO`, `AtualizarEquipamentoDTO`
  - Usar Enums tipados onde aplicável
  - _Requisitos: 1.2, 3.1, 3.2_

- [ ] 3. Adicionar `toDto()` nos Form Requests correspondentes
  - Popular `usuarioId` a partir de `$this->user()->id`
  - Converter strings para Enums via `from`
  - _Requisitos: 2.1, 2.2, 2.3_

- [ ] 4. Refatorar Services para receber DTOs
  - `OrdemServicoService`: `criarOrdemServico`, `alterarStatus`, `cancelar`
  - Serviços de Cliente/Equipamento (criar se ainda não existirem)
  - Remover parâmetros do tipo `array $dados`
  - _Requisitos: 4.1, 4.2_

- [ ] 5. Ajustar Controllers para passar DTOs
  - Substituir `$request->validated()` por `$request->toDto()`
  - Manter Controllers com uma única responsabilidade: HTTP↔Service
  - _Requisitos: 2.1_

- [ ] 6. Testes unitários dos DTOs
  - `tests/Unit/Application/OrdemServico/DTO/CriarOrdemServicoDTOTest.php`
  - Cobrir instanciação, tipos e imutabilidade (`readonly`)
  - _Requisitos: 1.1, 3.1, 3.2_

- [ ] 7. Testes de mapeamento Form Request → DTO
  - `tests/Unit/Http/Requests/StoreOrdemServicoRequestTest.php` (idem para os demais)
  - Fixar payload, validar `toDto()` retorna instância correta
  - _Requisitos: 2.1, 2.3_
