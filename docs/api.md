# Documentação da API — Sistema de Gestão de Ordens de Serviço

> **Base URL:** `http://localhost:8000/api/v1`
>
> **Autenticação:** Bearer Token via Sanctum.
> Inclua o header `Authorization: Bearer {token}` em todas as rotas protegidas.
> O token é obtido via `POST /auth/login`.

---

## Sumário

- [Autenticação](#autenticação)
- [Usuários](#usuários)
- [Clientes](#clientes)
- [Equipamentos](#equipamentos)
- [Ordens de Serviço](#ordens-de-serviço)
- [Dashboard](#dashboard)
- [Domínios de dados](#domínios-de-dados)
- [Formato de erro padrão](#formato-de-erro-padrão)

---

## Autenticação

### `POST /auth/login` · Público

Autentica o usuário e retorna um token Sanctum.

**Body:**
```json
{ "email": "admin@sistema.com.br", "password": "password123" }
```

**Resposta 200:**
```json
{
  "access_token": "1|abc...",
  "token_type": "Bearer",
  "user": { "id": 1, "name": "Admin", "email": "...", "perfil": "ADMINISTRADOR", "situacao": true }
}
```

**Erros:** `401` credenciais inválidas · `403` usuário desativado

---

### `POST /auth/logout` · 🔒

Revoga o token atual.

**Resposta 200:** `{ "message": "Logout realizado com sucesso." }`

---

### `GET /auth/me` · 🔒

Retorna os dados do usuário autenticado.

**Resposta 200:** `{ "id", "name", "email", "cargo", "perfil", "situacao" }`

---

## Usuários

> Exclusivo para perfil **ADMINISTRADOR**.

| Método | Rota | Descrição |
|--------|------|-----------|
| `GET` | `/usuarios` | Lista usuários paginados |
| `POST` | `/usuarios` | Cria usuário |
| `GET` | `/usuarios/{id}` | Detalhes do usuário |
| `PUT/PATCH` | `/usuarios/{id}` | Atualiza nome, email, cargo, senha |
| `PATCH` | `/usuarios/{id}/situacao` | Ativa ou desativa usuário |
| `PATCH` | `/usuarios/{id}/perfil` | Altera perfil (ADMINISTRADOR \| ATENDENTE) |

**Body — POST /usuarios:**
```json
{
  "name": "João Silva",
  "email": "joao@empresa.com",
  "password": "MinhaSenh4",
  "cargo": "Atendente Sr.",
  "perfil": "ATENDENTE"
}
```

**Body — PATCH /usuarios/{id}/situacao:**
```json
{ "situacao": false }
```

**Body — PATCH /usuarios/{id}/perfil:**
```json
{ "perfil": "ADMINISTRADOR" }
```

**Erros:** `403` perfil sem permissão · `422` auto-desativação bloqueada

---

## Clientes

| Método | Rota | Descrição |
|--------|------|-----------|
| `GET` | `/clientes` | Lista paginada com filtros |
| `POST` | `/clientes` | Cria cliente |
| `GET` | `/clientes/{id}` | Detalhes do cliente |
| `PUT/PATCH` | `/clientes/{id}` | Atualiza cliente |
| `DELETE` | `/clientes/{id}` | Desativa cliente (soft delete lógico) |

**Parâmetros GET /clientes:**

| Parâmetro | Tipo | Descrição |
|-----------|------|-----------|
| `search` | string | Busca em nome/documento |
| `situacao` | boolean | Filtra por situação ativa/inativa |
| `per_page` | int (1–100) | Itens por página (padrão: 15) |

**Body — POST /clientes:**
```json
{
  "tipo_pessoa": "J",
  "nome_razao_social": "Acme Ltda",
  "cpf_cnpj": "12345678000195",
  "email": "contato@acme.com",
  "telefone": "11999999999",
  "endereco": "Rua Teste, 1"
}
```

---

## Equipamentos

| Método | Rota | Descrição |
|--------|------|-----------|
| `GET` | `/equipamentos` | Lista paginada com filtros |
| `POST` | `/equipamentos` | Cria equipamento |
| `GET` | `/equipamentos/{id}` | Detalhes do equipamento |
| `PUT/PATCH` | `/equipamentos/{id}` | Atualiza equipamento |
| `DELETE` | `/equipamentos/{id}` | Desativa equipamento |

**Parâmetros GET /equipamentos:**

| Parâmetro | Tipo | Descrição |
|-----------|------|-----------|
| `cliente_id` | int | Filtra por cliente |
| `situacao` | boolean | Filtra por situação |
| `numero_serie` | string | Busca parcial por número de série |
| `per_page` | int (1–100) | Itens por página (padrão: 15) |

**Body — POST /equipamentos:**
```json
{
  "cliente_id": 1,
  "tipo_equipamento_id": 2,
  "numero_serie": "SN-001",
  "marca": "Dell",
  "descricao": "Notebook i7 16GB"
}
```

**Regras de negócio:**
- Número de série deve ser único no sistema (RN03).
- Equipamento deve pertencer a um cliente ativo (RN02).
- Não é possível trocar o `cliente_id` de um equipamento com OS ativa (RN05).

---

## Ordens de Serviço

| Método | Rota | Descrição |
|--------|------|-----------|
| `GET` | `/ordens-servico` | Lista paginada com filtros e ordenação |
| `POST` | `/ordens-servico` | Abre nova OS |
| `GET` | `/ordens-servico/{id}` | Detalhes completos da OS |
| `PATCH` | `/ordens-servico/{id}/status` | Altera status (fluxo intermediário) |
| `POST` | `/ordens-servico/{id}/concluir` | Conclui a OS com diagnóstico |
| `POST` | `/ordens-servico/{id}/cancelar` | Cancela a OS com motivo |

**Parâmetros GET /ordens-servico:**

| Parâmetro | Tipo | Valores |
|-----------|------|---------|
| `status` | string | `ABERTA`, `EM_ANALISE`, `EM_EXECUCAO`, `AGUARDANDO_CLIENTE`, `CONCLUIDA`, `CANCELADA` |
| `prioridade` | string | `BAIXA`, `MEDIA`, `ALTA`, `CRITICA` |
| `cliente_id` | int | ID do cliente |
| `numero` | string | Busca parcial no número da OS |
| `aberta_de` | date | Data de abertura inicial (YYYY-MM-DD) |
| `aberta_ate` | date | Data de abertura final (YYYY-MM-DD) |
| `sort` | string | Campo e direção: `data_abertura`, `-data_abertura`, `status`, `-status`, `prioridade`, `-prioridade`, `numero`, `-numero` |
| `per_page` | int (1–100) | Itens por página (padrão: 15) |

**Body — POST /ordens-servico:**
```json
{
  "cliente_id": 1,
  "descricao": "Notebook não liga",
  "prioridade": "ALTA",
  "equipamentos": [10, 20]
}
```

**Body — PATCH /ordens-servico/{id}/status:**
```json
{ "status": "EM_ANALISE", "diagnostico": "Verificando..." }
```
> ⚠️ `CONCLUIDA` e `CANCELADA` são rejeitadas nesta rota. Use `/concluir` e `/cancelar`.

**Body — POST /ordens-servico/{id}/concluir:**
```json
{ "diagnostico": "Cabo de alimentação substituído. Sistema operacional." }
```

**Body — POST /ordens-servico/{id}/cancelar:**
```json
{ "motivo": "Cliente cancelou o pedido de manutenção." }
```

### Máquina de estados

```
ABERTA ──► EM_ANALISE ──► EM_EXECUCAO ⇌ AGUARDANDO_CLIENTE
                                    └──► CONCLUIDA

ABERTA ──────────────────────────────► CANCELADA
EM_ANALISE ──────────────────────────► CANCELADA
```

**Regras:**
- OS `CONCLUIDA` não pode ser alterada (RN06).
- OS `CONCLUIDA` não pode ser cancelada (RN07).
- OS `CANCELADA` não aceita mais mudanças (RN08).
- `EM_EXECUCAO` ⇌ `AGUARDANDO_CLIENTE` são bidirecionais (RN09).

---

## Dashboard

### `GET /dashboard/indicadores` · 🔒

Retorna contagens operacionais em uma única query.

**Resposta 200:**
```json
{
  "por_status": {
    "ABERTA": 5,
    "EM_ANALISE": 3,
    "EM_EXECUCAO": 8,
    "AGUARDANDO_CLIENTE": 2,
    "CONCLUIDA": 42,
    "CANCELADA": 1
  },
  "concluidas_no_mes": 12
}
```

---

## Domínios de dados

### Prioridades
| Valor | Descrição |
|-------|-----------|
| `BAIXA` | Baixa urgência |
| `MEDIA` | Urgência normal |
| `ALTA` | Alta urgência |
| `CRITICA` | Crítico — impacto imediato |

### Status da OS
| Valor | Descrição |
|-------|-----------|
| `ABERTA` | Recém-criada, aguardando triagem |
| `EM_ANALISE` | Em avaliação técnica |
| `EM_EXECUCAO` | Em atendimento |
| `AGUARDANDO_CLIENTE` | Aguardando resposta/aprovação |
| `CONCLUIDA` | Encerrada com sucesso (terminal) |
| `CANCELADA` | Cancelada (terminal) |

### Perfis
| Valor | Descrição |
|-------|-----------|
| `ADMINISTRADOR` | Acesso total, incluindo gestão de usuários |
| `ATENDENTE` | Acesso a todas as operações, exceto gestão de usuários |

---

## Formato de erro padrão

**Erros de domínio (422/404/502):**
```json
{
  "message": "Transição inválida: ABERTA → CONCLUIDA.",
  "code": "TRANSICAO_STATUS_INVALIDA"
}
```

**Erros de validação (422):**
```json
{
  "message": "The given data was invalid.",
  "errors": {
    "campo": ["Mensagem de erro."]
  }
}
```

**Códigos de erro de domínio:**

| code | HTTP | Descrição |
|------|------|-----------|
| `TRANSICAO_STATUS_INVALIDA` | 422 | Transição proibida pela máquina de estados |
| `ORDEM_SERVICO_JA_CONCLUIDA` | 422 | OS já encerrada |
| `ORDEM_SERVICO_JA_CANCELADA` | 422 | OS já cancelada |
| `ORDEM_SERVICO_NAO_ENCONTRADA` | 404 | OS não existe |
| `CLIENTE_NAO_ENCONTRADO` | 404 | Cliente não existe |
| `EQUIPAMENTO_NAO_ENCONTRADO` | 404 | Equipamento não existe |
| `EQUIPAMENTO_NAO_PERTENCE_CLIENTE` | 422 | Equipamento de cliente diferente |
| `EQUIPAMENTO_INATIVO` | 422 | Equipamento desativado |
| `EQUIPAMENTO_COM_OS_ATIVA` | 422 | Equipamento com OS em andamento |
| `INTEGRACAO_ERP` | 502 | Falha na comunicação com o ERP |

---

> Documentação mantida em `docs/api.md`.
> Para novos endpoints, atualize esta tabela no mesmo PR.
