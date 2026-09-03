# Requisitos — Ações Dedicadas da OS

## Introdução

Hoje a mudança de status, o cancelamento e a conclusão de uma OS estão embutidos no `update` genérico ou em uma rota `cancelar` isolada. A seção 12 do escopo especifica ações separadas:

- `PATCH /ordens-servico/{id}/status`
- `POST  /ordens-servico/{id}/concluir`
- `POST  /ordens-servico/{id}/cancelar`

Esta spec cria essas ações, aposenta o `update` genérico e resolve dois pontos correlatos: preenchimento explícito de `data_abertura` no `store` e melhor nome de parâmetro de rota (`ordemServico` em vez de `ordens_servico`).

## Requisitos

### Requisito 1 — Rota dedicada de alteração de status

**User Story:** Como atendente, quero um endpoint específico para alterar o status da OS.

#### Critérios de Aceitação
1. QUANDO envio `PATCH /api/v1/ordens-servico/{id}/status` com `{ "status": "EM_EXECUCAO" }` ENTÃO o sistema DEVE aplicar a transição respeitando a máquina de estados (Spec 1).
2. QUANDO a transição é inválida ENTÃO o sistema DEVE responder 422 via `TransicaoStatusInvalidaException` (Spec 3).
3. QUANDO a transição é válida ENTÃO o histórico DEVE ser registrado com o usuário autenticado (Spec 2).
4. QUANDO a rota de alteração de status é chamada com destino CONCLUIDA ou CANCELADA ENTÃO o sistema DEVE recusar com 422, orientando a usar as rotas dedicadas `concluir`/`cancelar`.

### Requisito 2 — Rota dedicada de conclusão

**User Story:** Como atendente, quero um endpoint próprio para concluir a OS informando o diagnóstico final.

#### Critérios de Aceitação
1. QUANDO envio `POST /api/v1/ordens-servico/{id}/concluir` com `{ "diagnostico": "..." }` ENTÃO o sistema DEVE:
   - validar que o status atual permite ir a CONCLUIDA (apenas EM_EXECUCAO conforme Spec 1);
   - preencher `diagnostico` e `data_fechamento`;
   - alterar o status para CONCLUIDA;
   - registrar histórico.
2. QUANDO `diagnostico` estiver ausente ou vazio ENTÃO o sistema DEVE responder 422.

### Requisito 3 — Rota dedicada de cancelamento

**User Story:** Como atendente, quero cancelar uma OS informando o motivo.

#### Critérios de Aceitação
1. QUANDO envio `POST /api/v1/ordens-servico/{id}/cancelar` com `{ "motivo": "..." }` ENTÃO o sistema DEVE:
   - validar que a OS não está em estado terminal;
   - alterar o status para CANCELADA;
   - preencher `data_fechamento`;
   - registrar histórico com `motivo`.
2. QUANDO a OS já está CONCLUIDA ENTÃO o sistema DEVE responder 422 com `OrdemServicoJaConcluidaException`.
3. QUANDO a OS já está CANCELADA ENTÃO o sistema DEVE responder 422 com `OrdemServicoJaCanceladaException`.

### Requisito 4 — Remoção da rota `update` genérica

**User Story:** Como consumidor da API, quero endpoints com semântica clara.

#### Critérios de Aceitação
1. QUANDO a spec for aplicada ENTÃO `PUT/PATCH /api/v1/ordens-servico/{id}` NÃO DEVE mais aceitar mudança de status ou diagnóstico.
2. QUANDO a rota genérica for mantida ENTÃO ela DEVE permitir apenas atualização de campos "editáveis livremente" (por exemplo: `descricao`, `prioridade`), respeitando a máquina de estados (não editar OS terminal).
3. QUANDO ela não fizer sentido no domínio ENTÃO ela PODE ser removida por completo (decisão registrada no design).

### Requisito 5 — `data_abertura` explícita no `store`

**User Story:** Como desenvolvedor, quero que o Service preencha `data_abertura` para não depender do default do banco.

#### Critérios de Aceitação
1. QUANDO uma OS é criada ENTÃO `data_abertura` DEVE ser gravada como `now()` pelo Service, sem depender de `useCurrent()` do banco.
2. QUANDO o Model retorna após a criação ENTÃO `data_abertura` DEVE já estar populada sem necessidade de `refresh()`.

### Requisito 6 — Parâmetro de rota expressivo

**User Story:** Como desenvolvedor, quero um nome de parâmetro coerente.

#### Critérios de Aceitação
1. QUANDO as rotas de OS são declaradas ENTÃO o binding DEVE usar `{ordemServico}` (não `{ordens_servico}`).
2. QUANDO o controller injeta o Model ENTÃO a variável DEVE se chamar `$ordemServico`.
