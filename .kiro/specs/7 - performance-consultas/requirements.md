# Requisitos — Performance de Consultas

## Introdução

A seção 9.2 do escopo pede: paginação, cache, índices, eager loading e prevenção de N+1. Atualmente:

- Listagens usam `paginate(15)` fixo, sem filtros.
- Não há índices em colunas altamente filtradas (`ordens_servico.status`, `ordens_servico.cliente_id`, `ordens_servico.prioridade`, `equipamentos.situacao`).
- O `show` de OS carrega `historicos` mas não `historicos.usuario`, potencial N+1 se a Resource expuser autor.
- Não existe filtro por status/prioridade/período/cliente na listagem de OS.

Esta spec padroniza filtros, adiciona índices, elimina N+1 e permite `per_page` configurável.

## Requisitos

### Requisito 1 — Filtros e ordenação nas listagens

**User Story:** Como usuário, quero filtrar as listagens para localizar registros rapidamente.

#### Critérios de Aceitação
1. QUANDO `GET /api/v1/ordens-servico` recebe `?status=EM_EXECUCAO&prioridade=ALTA&cliente_id=10&numero=OS-2026&aberta_de=2026-01-01&aberta_ate=2026-12-31&sort=-data_abertura` ENTÃO o sistema DEVE aplicar todos os filtros combinados.
2. QUANDO `sort` é enviado ENTÃO o sistema DEVE aceitar `numero`, `data_abertura`, `data_fechamento`, `prioridade`, `status` (com `-` para desc) e rejeitar demais.
3. QUANDO `GET /api/v1/clientes` recebe `?situacao=true&search=acme` ENTÃO o sistema DEVE filtrar por situação e busca livre em `nome_razao_social`/`cpf_cnpj`.
4. QUANDO `GET /api/v1/equipamentos` recebe `?cliente_id=&situacao=&numero_serie=` ENTÃO o sistema DEVE aplicar os filtros.

### Requisito 2 — Paginação configurável e limitada

**User Story:** Como consumidor da API, quero definir o tamanho da página dentro de limites seguros.

#### Critérios de Aceitação
1. QUANDO `?per_page=N` é enviado ENTÃO o sistema DEVE respeitar o valor **dentro do intervalo [1, 100]**.
2. QUANDO `per_page` está fora do intervalo ENTÃO o sistema DEVE responder 422.
3. QUANDO `per_page` não é enviado ENTÃO o padrão DEVE ser 15.

### Requisito 3 — Eager loading e N+1

**User Story:** Como desenvolvedor, quero evitar N+1 nas respostas.

#### Critérios de Aceitação
1. QUANDO `GET /api/v1/ordens-servico/{id}` retorna a OS ENTÃO o sistema DEVE carregar `cliente`, `responsavel`, `itens.equipamento.tipoEquipamento`, `historicos.usuario` em uma única árvore de queries.
2. QUANDO a listagem paginada de OS retorna N registros ENTÃO o número de queries DEVE ser constante em relação a N (assert com `DB::listen`/spy).
3. QUANDO um teste ativa detecção de N+1 (`Model::preventLazyLoading(true)` em ambiente de teste) ENTÃO a suíte NÃO DEVE falhar por lazy loading nas rotas listadas.

### Requisito 4 — Índices de banco

**User Story:** Como DBA, quero índices nas colunas de filtro/join frequentes.

#### Critérios de Aceitação
1. QUANDO a spec é aplicada ENTÃO existe migration adicionando índices em:
   - `ordens_servico.status`
   - `ordens_servico.prioridade`
   - `ordens_servico.data_abertura`
   - `ordens_servico.cliente_id` (já indexado pela FK; garantir explicitamente)
   - `equipamentos.situacao`
   - `equipamentos.cliente_id` (idem FK)
   - `ordem_servico_itens.equipamento_id`
   - `historicos_os.ordem_servico_id`
2. QUANDO a migration for revertida (`down`) ENTÃO os índices adicionados DEVEM ser removidos.

### Requisito 5 — Contadores para dashboard (RF11)

**User Story:** Como atendente, quero um endpoint agregado para o dashboard.

#### Critérios de Aceitação
1. QUANDO `GET /api/v1/dashboard/indicadores` é chamado ENTÃO o sistema DEVE retornar contagens por status (ABERTA/EM_ANALISE/EM_EXECUCAO/AGUARDANDO_CLIENTE) e "concluídas no mês corrente".
2. QUANDO a consulta é executada ENTÃO ela DEVE ser feita em **uma única query** (`GROUP BY status`) e reaproveitar índice de `status`.
3. QUANDO habilitado cache ENTÃO o resultado PODE ser cacheado por 60 segundos.
