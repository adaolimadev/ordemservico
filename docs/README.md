# Documentação da API

Esta pasta contém a documentação da API de Gestão de Ordens de Serviço em três formatos:

| Arquivo | Formato | Uso |
|---------|---------|-----|
| [`openapi.yaml`](./openapi.yaml) | OpenAPI 3.1 | Especificação formal — importável em ferramentas |
| [`api-docs.html`](./api-docs.html) | Swagger UI | Visualização interativa no navegador |
| [`api.md`](./api.md) | Markdown | Referência rápida legível no editor/GitHub |

## Como visualizar a documentação interativa

### Opção 1 — Abrir o Swagger UI localmente

O arquivo `api-docs.html` carrega o Swagger UI via CDN e lê o `openapi.yaml`.
Como os navegadores bloqueiam `fetch` de arquivos via `file://`, sirva a pasta
por HTTP. A forma mais simples:

```bash
# a partir da pasta docs/
php -S localhost:8080
```

Depois acesse: <http://localhost:8080/api-docs.html>

> Alternativa sem PHP: `npx http-server docs -p 8080` ou a extensão **Live Server** do VS Code.

### Opção 2 — Colar no editor online

Copie o conteúdo de `openapi.yaml` em <https://editor.swagger.io>.

### Opção 3 — Importar no Postman / Insomnia

Ambos importam `openapi.yaml` diretamente e geram a coleção de requisições.

## Autenticação na documentação interativa

1. Rode `POST /auth/login` com as credenciais do seed
   (`admin@sistema.com.br` / `password123`).
2. Copie o `access_token` retornado.
3. Clique em **Authorize** no topo do Swagger UI e cole o token.
4. As rotas protegidas passam a enviar o header `Authorization: Bearer {token}`.

## Manutenção

Ao adicionar ou alterar um endpoint, atualize **`openapi.yaml`** (fonte formal)
e, se aplicável, a tabela em **`api.md`**. O `api-docs.html` não precisa de
alteração — ele sempre lê o `openapi.yaml` atual.
