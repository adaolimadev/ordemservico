# Plano de Implementação — Performance de Consultas

- [ ] 1. Criar `OrdemServicoIndexRequest`
  - Regras de filtros, `sort` restrito e `per_page` entre 1 e 100
  - _Requisitos: 1.1, 1.2, 2.1, 2.2, 2.3_

- [ ] 2. Implementar `scopeFiltrar` em `OrdemServico`
  - Aplicar `when` por filtro
  - _Requisitos: 1.1_

- [ ] 3. Refatorar `OrdemServicoController::index`
  - Injetar Form Request
  - Aplicar `with`, `filtrar`, `orderBy`, `paginate`
  - _Requisitos: 1.1, 1.2, 2.3, 3.2_

- [ ] 4. Ajustar `OrdemServicoController::show` para eager loading completo
  - `cliente`, `responsavel`, `itens.equipamento.tipoEquipamento`, `historicos.usuario`
  - _Requisitos: 3.1_

- [ ] 5. Criar controllers de índice equivalentes para Cliente e Equipamento
  - `ClienteIndexRequest`, `EquipamentoIndexRequest` (filtros da RF)
  - `scopeFiltrar` respectivos
  - _Requisitos: 1.3, 1.4, 2.1_

- [ ] 6. Migration de índices
  - `add_indexes_for_performance` (up/down)
  - _Requisitos: 4.1, 4.2_

- [ ] 7. Ativar `preventLazyLoading` fora de produção
  - `AppServiceProvider::boot`
  - _Requisitos: 3.3_

- [ ] 8. Criar `DashboardController@indicadores`
  - Rota `GET /api/v1/dashboard/indicadores`
  - Uma query com `GROUP BY status` + count mensal
  - _Requisitos: 5.1, 5.2_

- [ ] 9. Helper de teste `assertQueryCountLessThan`
  - `tests/Support/AssertsQueryCount.php`
  - _Requisitos: 3.2_

- [ ] 10. Testes de feature e performance
  - Filtros e ordenação combinados
  - `per_page` limites
  - N+1 na listagem/show de OS
  - Dashboard: 1 query para contagem por status
  - _Requisitos: 1.1–1.4, 2.1–2.3, 3.1–3.3, 5.1_
