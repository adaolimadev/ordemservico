# Requisitos — Máquina de Estados da OS

## Introdução

A Ordem de Serviço possui um ciclo de vida com transições de status permitidas e proibidas (RN06 a RN09 do escopo). Atualmente o `OrdemServicoService::atualizar` bloqueia apenas os estados terminais (CONCLUIDA e CANCELADA), permitindo qualquer outra transição entre os demais status. Esta spec formaliza a máquina de estados e centraliza a validação de transições, garantindo integridade das regras de negócio.

Regras cobertas: RN06, RN07, RN08, RN09 do documento `docs/Escopo Projeto.txt`.

## Requisitos

### Requisito 1 — Mapa de transições permitidas

**User Story:** Como desenvolvedor do domínio, quero declarar as transições válidas em um único local, para que a regra seja auditável e não fique espalhada em ifs pelo código.

#### Critérios de Aceitação
1. QUANDO um status é consultado quanto às suas transições permitidas ENTÃO o sistema DEVE retornar a lista abaixo:
   - ABERTA → EM_ANALISE, CANCELADA
   - EM_ANALISE → EM_EXECUCAO, CANCELADA
   - EM_EXECUCAO → AGUARDANDO_CLIENTE, CONCLUIDA
   - AGUARDANDO_CLIENTE → EM_EXECUCAO
   - CONCLUIDA → (vazio)
   - CANCELADA → (vazio)
2. QUANDO consultado se uma transição específica é permitida ENTÃO o sistema DEVE retornar boolean baseado no mapa acima.
3. QUANDO um status é terminal (CONCLUIDA ou CANCELADA) ENTÃO o sistema DEVE identificá-lo explicitamente como tal.

### Requisito 2 — Bloqueio de transições inválidas na atualização

**User Story:** Como atendente responsável pelo fluxo da OS, quero que o sistema rejeite mudanças de status que violem o fluxo, para preservar a integridade do processo.

#### Critérios de Aceitação
1. QUANDO uma OS CONCLUIDA recebe pedido de mudança para qualquer outro status ENTÃO o sistema DEVE rejeitar a operação (RN06, RN07).
2. QUANDO uma OS CANCELADA recebe pedido de mudança para EM_EXECUCAO ou qualquer outro status ENTÃO o sistema DEVE rejeitar (RN08).
3. QUANDO uma OS EM_EXECUCAO recebe pedido para AGUARDANDO_CLIENTE ENTÃO o sistema DEVE permitir (RN09).
4. QUANDO uma OS AGUARDANDO_CLIENTE recebe pedido para EM_EXECUCAO ENTÃO o sistema DEVE permitir (RN09).
5. QUANDO uma transição inválida é solicitada ENTÃO o sistema DEVE responder com HTTP 422 e mensagem indicando o status atual e o solicitado.
6. QUANDO o status solicitado é igual ao atual ENTÃO o sistema DEVE tratar como no-op (não gerar histórico duplicado), retornando 200.

### Requisito 3 — Idempotência de histórico e data_fechamento

**User Story:** Como auditor, quero que o histórico e a data de fechamento reflitam apenas transições reais.

#### Critérios de Aceitação
1. QUANDO uma transição válida ocorre ENTÃO o sistema DEVE registrar entrada em `historicos_os` com o novo status e o usuário responsável.
2. QUANDO a transição destino é CONCLUIDA ou CANCELADA ENTÃO o sistema DEVE preencher `data_fechamento` com o timestamp da transição.
3. QUANDO não há mudança efetiva de status ENTÃO o sistema NÃO DEVE gravar histórico nem alterar `data_fechamento`.

### Requisito 4 — Testabilidade da máquina

**User Story:** Como desenvolvedor, quero testar as transições sem depender do banco.

#### Critérios de Aceitação
1. QUANDO a lógica de transição é invocada ENTÃO ela DEVE poder ser testada unitariamente a partir do Enum, sem carregar Model nem banco.
2. QUANDO um teste percorre todas as combinações de status ENTÃO o resultado DEVE ser determinístico e cobrir os 6² = 36 pares.
