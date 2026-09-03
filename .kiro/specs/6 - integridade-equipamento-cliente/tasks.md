# Plano de Implementação — Integridade Equipamento-Cliente

- [ ] 1. Adicionar `possuiOsAtiva()` em `Equipamento`
  - Criar relação `itensOs()` se ainda não existir
  - Implementar consulta com `whereNotIn(status, [CONCLUIDA, CANCELADA])`
  - _Requisitos: 1.1, 1.2_

- [ ] 2. Reforçar `UpdateEquipamentoRequest`
  - Trocar rules para `sometimes` e resolver `$equipamento = $this->route(...)`
  - Adicionar closure de bloqueio para troca de cliente com OS ativa
  - `numero_serie` com `Rule::unique(...)->ignore($equipamento->id)`
  - _Requisitos: 1.1, 1.3, 2.1, 2.2_

- [ ] 3. Endpoint "equipamentos disponíveis" (opcional dentro desta spec)
  - `GET /api/v1/clientes/{cliente}/equipamentos?disponiveis=1`
  - Filtro `situacao = true` e sem OS ativa
  - _Requisitos: 4.1_

- [ ] 4. Testes de feature — Update de equipamento
  - `UpdateEquipamentoTest::nao_permite_trocar_cliente_com_os_ativa`
  - `UpdateEquipamentoTest::permite_trocar_cliente_sem_os_ativa`
  - `UpdateEquipamentoTest::numero_serie_unico_ignora_proprio_registro`
  - _Requisitos: 1.1, 1.2, 2.1, 2.2_

- [ ] 5. Testes de regressão — Criação de OS
  - `StoreOrdemServicoTest::rejeita_equipamentos_de_outro_cliente`
  - `StoreOrdemServicoTest::rejeita_equipamento_inativo`
  - _Requisitos: 3.1, 4.2_
