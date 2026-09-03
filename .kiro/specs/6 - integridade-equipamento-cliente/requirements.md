# Requisitos — Integridade Equipamento-Cliente

## Introdução

O escopo (RN02 e RN05) exige que todo equipamento pertença a exatamente um cliente e que todos os equipamentos de uma OS sejam do mesmo cliente. Hoje o `EquipamentoController::update` aceita `cliente_id` no payload sem verificar se o equipamento possui OS ativa — o que permitiria "migrar" um equipamento com OS aberta para outro cliente e violar a RN05 retroativamente. Esta spec bloqueia essa alteração e reforça a integridade.

## Requisitos

### Requisito 1 — Bloqueio de troca de cliente com OS ativa

**User Story:** Como administrador, quero impedir a troca de cliente em equipamentos que ainda têm OS ativa.

#### Critérios de Aceitação
1. QUANDO um `PUT/PATCH /api/v1/equipamentos/{id}` altera `cliente_id` E existe ao menos uma OS deste equipamento com status ≠ CONCLUIDA e ≠ CANCELADA ENTÃO o sistema DEVE responder 422 com `EquipamentoComOsAtivaException`.
2. QUANDO nenhuma OS ativa referencia o equipamento ENTÃO a troca DEVE ser permitida.
3. QUANDO o `cliente_id` enviado é igual ao atual ENTÃO o sistema DEVE tratar como no-op e não aplicar a regra.

### Requisito 2 — Reforço da unicidade de número de série (RN03)

**User Story:** Como responsável pelo cadastro, quero garantir que o número de série é único mesmo em atualização.

#### Critérios de Aceitação
1. QUANDO um equipamento é atualizado ENTÃO a validação de `numero_serie` DEVE ignorar o próprio registro (`unique:equipamentos,numero_serie,{id}`).
2. QUANDO tento gravar um número de série já usado por outro equipamento ENTÃO o sistema DEVE responder 422.

### Requisito 3 — Consistência da RN05 na criação de OS (revisão)

**User Story:** Como reviewer, quero garantir que a regra "todos equipamentos do mesmo cliente" continua vigente após alterações.

#### Critérios de Aceitação
1. QUANDO uma OS é criada com equipamentos de clientes diferentes do `cliente_id` da OS ENTÃO o sistema DEVE rejeitar (situação já coberta em `StoreOrdemServicoRequest`, mas deve haver teste de regressão).
2. QUANDO a validação existente já cobre o caso ENTÃO NÃO haverá alteração de código, apenas cobertura por teste.

### Requisito 4 — Situação do equipamento (RN04)

**User Story:** Como atendente, quero que equipamentos inativos não sejam elegíveis para novas OS.

#### Critérios de Aceitação
1. QUANDO um equipamento está com `situacao = false` ENTÃO ele NÃO DEVE aparecer em endpoints de "equipamentos disponíveis para OS".
2. QUANDO tento incluir um equipamento inativo no `store` de OS ENTÃO o sistema DEVE rejeitar (já coberto, garantir teste de regressão).
