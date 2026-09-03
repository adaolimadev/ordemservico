# Plano de Implementação — Refinamentos Gerais

- [ ] 1. Ajustar Resources para expor Enums como `value`
  - `OrdemServicoResource`: `status => $this->status->value`, `prioridade => $this->prioridade->value`
  - Revisar outras Resources para casos análogos
  - _Requisitos: 1.1, 1.2, 1.3_

- [ ] 2. Migration para histórico com `status_anterior`
  - Criar `add_status_anterior_and_motivo_to_historicos_os`
  - Renomear `status` para `status_novo` OU adicionar `status_novo` + `status_anterior`
  - Adicionar coluna `motivo` (nullable, text)
  - `down` reverte
  - _Requisitos: 2.1, 2.2, 2.3, 2.4_

- [ ] 3. Ajustar `HistoricoOs` (Model)
  - `fillable` inclui `status_anterior`, `status_novo`, `motivo`
  - `casts` de Enums
  - _Requisitos: 2.1_

- [ ] 4. Ajustar Services para preencher `status_anterior`
  - `criarOrdemServico`: `status_anterior = null`, `status_novo = ABERTA`
  - `alterarStatus`/`concluir`/`cancelar`: usar o status antes da mudança
  - Cancelar grava `motivo`
  - _Requisitos: 2.1, 2.2, 2.3_

- [ ] 5. Resolver dependência `laravel/pao`
  - Verificar via `composer show laravel/pao` se existe; se não, substituir por `pestphp/pest` ou remover
  - Atualizar `composer.lock`
  - _Requisitos: 3.1, 3.2_

- [ ] 6. Documentação da API
  - Criar `docs/api.md` com tabela de endpoints, ou
  - Gerar `openapi.yaml` inicial cobrindo Auth, Clientes, Equipamentos, OS
  - _Requisitos: 4.1_

- [ ] 7. Revisão de idioma / nomes
  - Passar em rotas, colunas e propriedades checando consistência
  - Documentar exceções (`users.name`, `users.email`) em `docs/notes.txt`
  - _Requisitos: 5.1, 5.2_

- [ ] 8. Testes
  - `HistoricoOsTest`: cria OS e valida `status_anterior = null`
  - `HistoricoOsTest`: alterar status grava par correto
  - `HistoricoOsTest`: cancelamento grava `motivo`
  - Test de contrato: `OrdemServicoResource` retorna Enum como string
  - _Requisitos: 1.3, 2.1, 2.2, 2.3_
