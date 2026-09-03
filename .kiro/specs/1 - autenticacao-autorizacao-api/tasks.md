# Plano de Implementação — Autenticação e Autorização da API

- [ ] 1. Preparar o modelo `User` para o perfil
  - Verificar migration existente `alter_users_table_for_usuarios`
  - Adicionar coluna `perfil` (string) em `users` via nova migration, se ainda não existir
  - Criar `App\Enums\PerfilEnum` com apenas `ADMINISTRADOR` e `ATENDENTE`
  - Adicionar método `podeGerenciarUsuarios(): bool` no Enum
  - Ajustar `User::casts()` para expor `perfil` como `PerfilEnum`
  - Atualizar `UserFactory` (default = ATENDENTE)
  - _Requisitos: 3.1–3.6_

- [ ] 2. Criar `AuthController` e rotas de autenticação
  - `POST /api/v1/auth/login` — valida credenciais e emite token Sanctum
  - Rejeitar login se `situacao === false` (403)
  - `POST /api/v1/auth/logout` — revoga token atual
  - `GET /api/v1/auth/me` — retorna usuário autenticado (com perfil)
  - _Requisitos: 1.1–1.5_

- [ ] 3. Middleware `EnsureUsuarioAtivo`
  - Cria middleware que retorna 401 quando `user->situacao === false`
  - Registrar em `bootstrap/app.php` com alias
  - _Requisitos: 2.2_

- [ ] 4. Proteger rotas de negócio
  - Envolver o grupo `v1` com `middleware(['auth:sanctum', EnsureUsuarioAtivo::class])`
  - Manter `auth/login` fora do middleware
  - _Requisitos: 2.1, 2.2_

- [ ] 5. Definir Gate `gerenciar-usuarios`
  - Em `AppServiceProvider::boot()`: `Gate::define('gerenciar-usuarios', ...)` verificando `PerfilEnum::ADMINISTRADOR`
  - _Requisitos: 3.1, 3.3, 6.5_

- [ ] 6. Criar `UsuarioController` e rotas restritas
  - CRUD completo: `index`, `show`, `store`, `update`, `destroy` (desativação lógica)
  - `alterarSituacao(Usuario $usuario)` — impede auto-bloqueio
  - `alterarPerfil(Usuario $usuario, AlterarPerfilRequest)` — aceita apenas ADMINISTRADOR/ATENDENTE
  - Envolver grupo com `middleware('can:gerenciar-usuarios')`
  - Form Requests: `StoreUsuarioRequest`, `UpdateUsuarioRequest`, `AlterarPerfilRequest`
  - _Requisitos: 3.3, 6.1–6.5_

- [ ] 7. Ativar `authorize()` nos Form Requests de usuário
  - `StoreUsuarioRequest`, `UpdateUsuarioRequest`, `AlterarPerfilRequest` retornam `can('gerenciar-usuarios')`
  - Demais Form Requests (Cliente/Equipamento/OS) permanecem com `authorize(): true` (autenticação já basta)
  - _Requisitos: 5.1, 5.2, 5.3_

- [ ] 8. Remover `usuario_id` do payload da OS
  - Retirar `usuario_id` de `StoreOrdemServicoRequest` e `UpdateOrdemServicoRequest`
  - Ajustar `OrdemServicoController` para passar `Auth::id()` ao Service
  - Ajustar `OrdemServicoService::criarOrdemServico`, `alterarStatus` e `cancelar` para receber `int $usuarioId` como parâmetro tipado
  - _Requisitos: 4.1–4.4_

- [ ] 9. Testes de autenticação
  - `tests/Feature/Auth/LoginTest.php`: credenciais válidas/inválidas, usuário desativado, logout, me
  - `tests/Feature/Auth/RotasProtegidasTest.php`: rotas sem token retornam 401
  - _Requisitos: 1.1–1.5, 2.1, 2.2_

- [ ] 10. Testes de autorização por perfil
  - `tests/Feature/Auth/PerfilAtendenteTest.php`
    - Atendente acessa todos os endpoints operacionais (Cliente/Equipamento/OS/Dashboard) com sucesso
    - Atendente recebe 403 em qualquer `/api/v1/usuarios/*`
  - `tests/Feature/Auth/PerfilAdministradorTest.php`
    - Administrador acessa endpoints operacionais e de usuários
    - Administrador tentando desativar a si mesmo → 422
  - _Requisitos: 3.1–3.6, 6.1–6.5_

- [ ] 11. Teste de segurança do `usuario_id`
  - `tests/Feature/OrdemServico/UsuarioIdSpoofingTest.php`
  - Envia `usuario_id` diferente do autenticado; asserta que o gravado é o `Auth::id()`
  - _Requisitos: 4.1–4.4_
