# Plano de Implementação — Ações Dedicadas da OS

- [ ] 1. Criar Form Requests
  - `AlterarStatusOrdemServicoRequest` (com `Rule::notIn([CONCLUIDA, CANCELADA])`)
  - `ConcluirOrdemServicoRequest` (`diagnostico` obrigatório)
  - `CancelarOrdemServicoRequest` (`motivo` obrigatório)
  - Cada um com `toDto()` (integra com Spec 4)
  - _Requisitos: 1.4, 2.2, 3.1_

- [ ] 2. Criar DTOs correspondentes
  - `AlterarStatusOrdemServicoDTO`, `ConcluirOrdemServicoDTO`, `CancelarOrdemServicoDTO`
  - _Requisitos: 1.1, 2.1, 3.1_

- [ ] 3. Refatorar `OrdemServicoService`
  - Implementar `alterarStatus`, `concluir`, `cancelar` recebendo DTOs
  - Reaproveitar `StatusOSEnum::podeTransitarPara` (Spec 1)
  - Preencher `data_fechamento` nas ações terminais
  - Gravar `motivo` no histórico ao cancelar
  - Passar a preencher `data_abertura = now()` em `criarOrdemServico`
  - Remover `atualizar` genérico
  - _Requisitos: 1.1–1.3, 2.1, 3.1, 4.1, 4.2, 4.3, 5.1, 5.2_

- [ ] 4. Ajustar rotas
  - Aplicar `parameters(['ordens-servico' => 'ordemServico'])`
  - Adicionar as três novas rotas
  - Excluir `update` e `destroy` do `apiResource` (a menos que decisão contrária no design)
  - Envolver com `auth:sanctum` (Spec 2)
  - _Requisitos: 1.1, 2.1, 3.1, 4.1, 6.1_

- [ ] 5. Ajustar `OrdemServicoController`
  - Adicionar métodos `alterarStatus`, `concluir`, `cancelar`
  - Renomear parâmetro para `$ordemServico`
  - Remover método `update` antigo
  - _Requisitos: 1.1–1.3, 2.1, 3.1, 4.1, 6.1, 6.2_

- [ ] 6. Testes de feature
  - `tests/Feature/OrdemServico/AlterarStatusTest.php`
  - `tests/Feature/OrdemServico/ConcluirOrdemServicoTest.php`
  - `tests/Feature/OrdemServico/CancelarOrdemServicoTest.php`
  - `tests/Feature/OrdemServico/DataAberturaExplicitaTest.php`
  - `tests/Feature/OrdemServico/RotaUpdateGenericaRemovidaTest.php` (asserta 405)
  - _Requisitos: 1.1–1.4, 2.1, 2.2, 3.1–3.3, 4.1, 5.1, 5.2_

- [ ] 7. Documentar quebra
  - Atualizar README ou CHANGELOG com a remoção do endpoint genérico
  - _Requisitos: 4.1_
