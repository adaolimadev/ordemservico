# Plano de Implementação — Máquina de Estados da OS

- [ ] 1. Estender `StatusOSEnum` com a máquina de estados
  - Adicionar `transicoesPermitidas(): array`
  - Adicionar `podeTransitarPara(self $destino): bool`
  - Adicionar `ehTerminal(): bool`
  - _Requisitos: 1.1, 1.2, 1.3_

- [ ] 2. Criar exceção de domínio `TransicaoStatusInvalidaException`
  - Criar em `app/Exceptions/Domain/TransicaoStatusInvalidaException.php`
  - Guardar `StatusOSEnum $atual` e `StatusOSEnum $destino` na exceção
  - Construir mensagem `"Transição inválida: {atual} → {destino}"`
  - _Requisitos: 2.5_

- [ ] 3. Refatorar `OrdemServicoService`
  - Extrair método `alterarStatus(OrdemServico $os, StatusOSEnum $novo, int $usuarioId): OrdemServico`
  - Tratar caso idempotente (mesmo status → no-op)
  - Delegar validação à `podeTransitarPara`
  - Preencher `data_fechamento` quando destino for terminal
  - Ajustar `atualizar` para usar `alterarStatus` internamente enquanto Spec 5 não é aplicada
  - _Requisitos: 2.1–2.6, 3.1–3.3_

- [ ] 4. Testes unitários do Enum
  - Criar `tests/Unit/Enums/StatusOSEnumTest.php`
  - Cobrir 36 pares (matriz completa) com data provider
  - Validar `ehTerminal` para CONCLUIDA e CANCELADA
  - _Requisitos: 4.1, 4.2_

- [ ] 5. Testes de feature para transições
  - Criar `tests/Feature/OrdemServico/AlterarStatusTest.php`
  - Cenário: transição válida grava histórico e retorna 200
  - Cenário: transição inválida retorna 422 com mensagem específica
  - Cenário: mesmo status é no-op (sem histórico extra)
  - Cenário: transição para terminal preenche `data_fechamento`
  - _Requisitos: 2.1–2.6, 3.1–3.3_
