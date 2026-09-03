# Requisitos — Refinamentos Gerais

## Introdução

Conjunto de refinamentos menores que aparecem espalhados pelo projeto e melhoram consistência, rastreabilidade e higiene. Não têm impacto isolado grande, mas em conjunto elevam o padrão do código.

## Requisitos

### Requisito 1 — Enums nas Resources como `value`

**User Story:** Como consumidor da API, quero receber Enums como strings estáveis (`ABERTA`), não como objetos.

#### Critérios de Aceitação
1. QUANDO `OrdemServicoResource` serializa `status` e `prioridade` ENTÃO os campos DEVEM ser retornados como `->value` (string).
2. QUANDO qualquer outra Resource expõe campo Enum ENTÃO o mesmo padrão DEVE ser seguido.
3. QUANDO um teste verifica o payload ENTÃO ele DEVE poder asserir `assertJsonPath('data.status', 'ABERTA')`.

### Requisito 2 — Histórico com `status_anterior`

**User Story:** Como auditor, quero saber de qual status a OS veio, além do para qual foi.

#### Critérios de Aceitação
1. QUANDO uma transição de status ocorre ENTÃO o histórico DEVE gravar `status_anterior` e `status_novo` (renomeando/estendendo o atual `status`).
2. QUANDO a criação da OS ocorre ENTÃO o histórico inicial DEVE ter `status_anterior = null` e `status_novo = ABERTA`.
3. QUANDO um cancelamento ocorre ENTÃO o histórico DEVE registrar também `motivo` (integra com Spec 5).
4. QUANDO a migração de banco é executada ENTÃO DEVE existir migration adicionando/renomeando colunas em `historicos_os`.

### Requisito 3 — Dependência `laravel/pao` no composer

**User Story:** Como novo desenvolvedor, quero que `composer install` funcione sem surpresas.

#### Critérios de Aceitação
1. QUANDO o `composer.json` é inspecionado ENTÃO a dependência `laravel/pao` DEVE ser validada como real (é typo? substituir por `pestphp/pest` ou remover).
2. QUANDO decidido remover/substituir ENTÃO o `composer.lock` DEVE ser atualizado.

### Requisito 4 — Documentação mínima da API

**User Story:** Como consumidor externo, quero uma referência da API.

#### Critérios de Aceitação
1. QUANDO o projeto é iniciado ENTÃO um arquivo `docs/api.md` OU um `openapi.yaml` DEVE listar endpoints, métodos, corpo e respostas principais.
2. QUANDO um novo endpoint é adicionado ENTÃO a documentação DEVE ser atualizada no mesmo PR.

### Requisito 5 — Padronização de idioma

**User Story:** Como time de desenvolvimento, quero consistência no idioma dos identificadores.

#### Critérios de Aceitação
1. QUANDO nomes de tabelas, colunas, rotas e propriedades JSON são revisados ENTÃO o padrão DEVE ser `snake_case` em português para colunas e rotas, e `camelCase` para nomes de variáveis PHP.
2. QUANDO houver campos em inglês herdados (ex.: `name`, `email` em `users`) ENTÃO eles DEVEM ser mantidos, sem forçar tradução.
