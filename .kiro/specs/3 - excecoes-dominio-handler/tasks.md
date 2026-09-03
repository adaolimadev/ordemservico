# Plano de Implementação — Exceções de Domínio e Handler Centralizado

- [ ] 1. Criar base `DomainException`
  - `app/Exceptions/Domain/DomainException.php` (abstrata)
  - Métodos `httpStatus()`, `errorCode()`, `context()`
  - _Requisitos: 1.1_

- [ ] 2. Criar exceções concretas
  - `TransicaoStatusInvalidaException` (guarda atual/destino)
  - `ClienteNaoEncontradoException`
  - `EquipamentoNaoEncontradoException`
  - `EquipamentoNaoPertenceAoClienteException`
  - `EquipamentoInativoException`
  - `EquipamentoComOsAtivaException`
  - `OrdemServicoNaoEncontradaException`
  - `OrdemServicoJaCanceladaException`
  - `OrdemServicoJaConcluidaException`
  - `IntegracaoErpException` (httpStatus 502)
  - _Requisitos: 1.2, 2.3, 2.4, 2.5_

- [ ] 3. Registrar render central em `bootstrap/app.php`
  - Callback `render(DomainException $e, Request $request)` retornando JSON
  - Log `warning` para <500; `error` para ≥500
  - Manter `shouldRenderJsonWhen` já existente
  - _Requisitos: 2.1, 2.2, 4.1, 4.2, 4.3_

- [ ] 4. Substituir usos de `\Exception` no Service
  - `OrdemServicoService::cancelar` → `OrdemServicoJaConcluidaException`, `OrdemServicoJaCanceladaException`
  - `OrdemServicoService::atualizar/alterarStatus` → `TransicaoStatusInvalidaException`
  - _Requisitos: 1.1, 3.2_

- [ ] 5. Remover try/catch dos controllers
  - `OrdemServicoController::cancelar` e `::update` sem try/catch
  - _Requisitos: 3.1, 3.2_

- [ ] 6. Testes
  - `tests/Feature/Exceptions/DomainExceptionRenderTest.php`: cenário por exceção, valida status/payload
  - `tests/Unit/Exceptions/DomainExceptionTest.php`: `errorCode()` derivado corretamente
  - Uso de `Log::spy()` em cenário 502 para validar `error` log
  - _Requisitos: 2.1–2.6, 4.1–4.3_
