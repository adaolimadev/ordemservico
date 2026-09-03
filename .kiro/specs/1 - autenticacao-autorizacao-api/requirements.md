# Requisitos — Autenticação e Autorização da API

## Introdução

Atualmente as rotas `v1` estão públicas (não há middleware `auth:sanctum`), todos os Form Requests retornam `authorize(): true` e o `usuario_id` vem no corpo da requisição — permitindo que qualquer chamador se passe por outro usuário. Esta spec introduz autenticação via Sanctum, autorização por dois perfis (Administrador e Atendente) e substitui `usuario_id` do payload pelo usuário autenticado.

Regras cobertas: RNF 9.1 (segurança), RF01, RF02 e perfis descritos na seção 2 do escopo.

## Perfis do sistema

O sistema possui exatamente **dois** perfis de acesso:

- **ADMINISTRADOR** — acesso total, incluindo o gerenciamento de usuários (criar, listar, editar, ativar/desativar e alterar perfil de qualquer usuário).
- **ATENDENTE** — acesso a todas as funcionalidades operacionais do sistema (Clientes, Equipamentos, Ordens de Serviço, Dashboard) **exceto** o gerenciamento de usuários.

Escopo de "gerenciamento de usuários" (exclusivo do Administrador):

- Criar novo usuário.
- Listar usuários.
- Visualizar detalhes de outro usuário.
- Editar dados de outro usuário.
- Ativar ou desativar outro usuário.
- Alterar o perfil de outro usuário.

Atendentes podem acessar apenas os próprios dados via `GET /api/v1/auth/me` e realizar login/logout.

## Requisitos

### Requisito 1 — Login e emissão de token

**User Story:** Como usuário, quero autenticar com e-mail e senha para obter um token e consumir a API.

#### Critérios de Aceitação
1. QUANDO envio credenciais válidas para `POST /api/v1/auth/login` ENTÃO o sistema DEVE retornar 200 com `access_token` e dados básicos do usuário (incluindo o perfil).
2. QUANDO envio credenciais inválidas ENTÃO o sistema DEVE retornar 401.
3. QUANDO o usuário está com `situacao = false` (desativado) ENTÃO o sistema DEVE recusar o login com 403.
4. QUANDO envio `POST /api/v1/auth/logout` com token válido ENTÃO o token DEVE ser revogado.
5. QUANDO envio `GET /api/v1/auth/me` com token válido ENTÃO o sistema DEVE retornar os dados do usuário autenticado, incluindo o perfil.

### Requisito 2 — Proteção das rotas de negócio

**User Story:** Como responsável pela segurança, quero que todas as rotas de negócio exijam autenticação.

#### Critérios de Aceitação
1. QUANDO uma rota de `/api/v1/*` (exceto `auth/login`) é chamada sem token válido ENTÃO o sistema DEVE retornar 401.
2. QUANDO o token é válido mas o usuário associado foi desativado ENTÃO o sistema DEVE retornar 401.

### Requisito 3 — Perfis e autorização

**User Story:** Como administrador, quero que Atendentes possam operar o sistema mas não gerenciar usuários.

#### Critérios de Aceitação
1. QUANDO o perfil é ADMINISTRADOR ENTÃO o sistema DEVE permitir todas as operações, sem restrição.
2. QUANDO o perfil é ATENDENTE ENTÃO o sistema DEVE permitir todas as operações de Clientes, Equipamentos, Ordens de Serviço (criação, listagem, alteração de status, conclusão, cancelamento) e Dashboard.
3. QUANDO o perfil é ATENDENTE E a rota é qualquer endpoint de gerenciamento de usuários (`GET /api/v1/usuarios`, `POST /api/v1/usuarios`, `PUT/PATCH /api/v1/usuarios/{id}`, `DELETE /api/v1/usuarios/{id}`, `PATCH /api/v1/usuarios/{id}/situacao`, `PATCH /api/v1/usuarios/{id}/perfil`) ENTÃO o sistema DEVE responder 403.
4. QUANDO o perfil é ATENDENTE E a rota é `GET /api/v1/auth/me` ENTÃO o sistema DEVE permitir (retorna apenas dados próprios).
5. QUANDO um Atendente tenta acessar `POST /api/v1/auth/logout` ENTÃO o sistema DEVE permitir (encerra a própria sessão).
6. QUANDO um perfil sem permissão tenta executar uma ação ENTÃO o sistema DEVE responder 403 com mensagem informativa, sem vazar detalhes internos.

### Requisito 4 — Uso do usuário autenticado em vez de `usuario_id` no payload

**User Story:** Como responsável pela segurança, quero impedir que o cliente escolha por qual usuário registra ações.

#### Critérios de Aceitação
1. QUANDO uma OS é criada ENTÃO o sistema DEVE gravar `usuario_id` a partir de `Auth::id()`, ignorando qualquer `usuario_id` enviado no corpo.
2. QUANDO um status de OS é alterado ENTÃO o histórico DEVE registrar o usuário autenticado como autor.
3. QUANDO uma OS é cancelada ENTÃO o histórico DEVE registrar o usuário autenticado.
4. QUANDO o cliente envia `usuario_id` no payload ENTÃO o sistema DEVE ignorar silenciosamente (não deve falhar validação por isso).

### Requisito 5 — Form Requests autorizam a partir do perfil

**User Story:** Como desenvolvedor, quero que a autorização por perfil viva no Form Request, próxima da rota.

#### Critérios de Aceitação
1. QUANDO um Form Request é executado ENTÃO `authorize()` DEVE consultar o perfil do usuário autenticado.
2. QUANDO `authorize()` retorna false ENTÃO o sistema DEVE responder 403 (não 401).
3. QUANDO um endpoint não exige perfil específico (leitura genérica autenticada) ENTÃO `authorize()` PODE retornar `true`, mas o middleware de autenticação continua obrigatório.

### Requisito 6 — Gerenciamento de usuários (exclusivo do Administrador)

**User Story:** Como administrador, quero cadastrar e manter os usuários do sistema.

#### Critérios de Aceitação
1. QUANDO um ADMINISTRADOR chama `POST /api/v1/usuarios` com dados válidos ENTÃO o sistema DEVE criar o usuário com o perfil informado (ADMINISTRADOR ou ATENDENTE).
2. QUANDO um ADMINISTRADOR chama `PATCH /api/v1/usuarios/{id}/situacao` ENTÃO o sistema DEVE ativar ou desativar o usuário.
3. QUANDO um ADMINISTRADOR chama `PATCH /api/v1/usuarios/{id}/perfil` ENTÃO o sistema DEVE alterar o perfil do usuário.
4. QUANDO um ADMINISTRADOR tenta desativar a si mesmo ENTÃO o sistema DEVE responder 422 (impede se auto-bloquear).
5. QUANDO um ATENDENTE chama qualquer endpoint sob `/api/v1/usuarios/*` (exceto `auth/me`) ENTÃO o sistema DEVE responder 403.
