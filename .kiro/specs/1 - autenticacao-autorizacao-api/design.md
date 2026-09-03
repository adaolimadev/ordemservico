# Design — Autenticação e Autorização da API

## Visão Geral

Autenticação via **Laravel Sanctum** (já instalado) com tokens pessoais. Autorização baseada em **dois perfis** (`ADMINISTRADOR` e `ATENDENTE`) implementada com **Policies + Gates** e um Enum `PerfilEnum`. Como a matriz de permissões é simples — "Atendente pode tudo, menos gerenciar usuários" — o design privilegia clareza: apenas as ações de gerenciamento de usuário exigem verificação de perfil; o restante exige apenas autenticação.

O `AuthController` expõe `login/logout/me`. As rotas de negócio ficam sob `middleware('auth:sanctum')` e as rotas de usuário ficam adicionalmente sob um gate/policy que exige `ADMINISTRADOR`.

## Arquitetura

```
Request
   │
   ▼
auth:sanctum ──► EnsureUsuarioAtivo ──► [rotas /usuarios/*] ──► can:gerenciar-usuarios ──► Controller
                                     └─► [demais rotas]     ─────────────────────────────► Controller
```

## Componentes e Interfaces

### `App\Enums\PerfilEnum`

```php
enum PerfilEnum: string
{
    case ADMINISTRADOR = 'ADMINISTRADOR';
    case ATENDENTE     = 'ATENDENTE';

    public function podeGerenciarUsuarios(): bool
    {
        return $this === self::ADMINISTRADOR;
    }
}
```

O `User` recebe cast `perfil => PerfilEnum::class` (ou expõe via relação `perfilUsuario->codigo` transformado em Enum, dependendo da estrutura atual da tabela `perfis_usuarios`).

### `App\Http\Controllers\Api\AuthController`

- `POST /api/v1/auth/login` — valida credenciais, checa `situacao`, emite token.
- `POST /api/v1/auth/logout` — revoga o token atual.
- `GET  /api/v1/auth/me` — retorna usuário autenticado (id, nome, email, perfil).

### Middleware `EnsureUsuarioAtivo`

Executa após `auth:sanctum`. Se `Auth::user()->situacao === false`, retorna 401.

### Autorização — abordagem simples com Gate

Como só existe uma "permissão especial" (gerenciar usuários), define-se um único Gate:

```php
// AppServiceProvider::boot()
Gate::define('gerenciar-usuarios', fn (User $user) =>
    $user->perfil === PerfilEnum::ADMINISTRADOR
);
```

Todas as demais rotas de negócio são autorizadas por autenticação simples — o Atendente pode tudo.

### Rotas

```php
Route::middleware(['auth:sanctum', EnsureUsuarioAtivo::class])
    ->prefix('v1')->group(function () {

        // Todas as rotas de negócio: autenticação basta
        Route::apiResource('clientes',        ClienteController::class);
        Route::apiResource('equipamentos',    EquipamentoController::class);
        Route::apiResource('ordens-servico',  OrdemServicoController::class);
        Route::get('dashboard/indicadores',   [DashboardController::class, 'indicadores']);

        // Rotas de gerenciamento de usuários: apenas Administrador
        Route::middleware('can:gerenciar-usuarios')->group(function () {
            Route::apiResource('usuarios', UsuarioController::class);
            Route::patch('usuarios/{usuario}/situacao', [UsuarioController::class, 'alterarSituacao']);
            Route::patch('usuarios/{usuario}/perfil',   [UsuarioController::class, 'alterarPerfil']);
        });
    });
```

### Matriz de permissões

| Ação                                              | ADMINISTRADOR | ATENDENTE |
| ------------------------------------------------- | :-----------: | :-------: |
| Auth: login / logout / me                          |      ✓       |     ✓     |
| Clientes: listar / ver / criar / editar / desativar |      ✓       |     ✓     |
| Equipamentos: listar / ver / criar / editar         |      ✓       |     ✓     |
| OS: listar / ver / criar                            |      ✓       |     ✓     |
| OS: alterar status / concluir / cancelar            |      ✓       |     ✓     |
| OS: registrar diagnóstico                           |      ✓       |     ✓     |
| Dashboard / indicadores                             |      ✓       |     ✓     |
| Usuários: listar / ver / criar / editar             |      ✓       |     –     |
| Usuários: ativar/desativar                          |      ✓       |     –     |
| Usuários: alterar perfil                            |      ✓       |     –     |

### Form Requests

Para rotas de gerenciamento de usuários, o Form Request confirma a autorização por defesa em profundidade:

```php
public function authorize(): bool
{
    return $this->user()?->can('gerenciar-usuarios') ?? false;
}
```

Para os demais Form Requests (Cliente/Equipamento/OS), `authorize()` retorna `true` — a autenticação por Sanctum já é suficiente.

### `UsuarioController`

Responsável pelo CRUD de usuários:

- `index`, `show`, `store`, `update`, `destroy` (ou `destroy` como desativação lógica, para manter o padrão do Cliente).
- `alterarSituacao(Usuario $usuario)` — ativa/desativa. Rejeita se `$usuario->id === Auth::id()` (proteção contra auto-bloqueio, Requisito 6.4).
- `alterarPerfil(Usuario $usuario, AlterarPerfilRequest $r)` — recebe `perfil: 'ADMINISTRADOR'|'ATENDENTE'`.

### Remoção de `usuario_id` do payload

- `StoreOrdemServicoRequest` e `UpdateOrdemServicoRequest` deixam de listar `usuario_id` em `rules()`.
- Controllers/Services passam a receber `Auth::id()` explicitamente (ou via DTO, ver Spec 4).
- Se o cliente enviar `usuario_id`, ele é descartado (não usar `$request->validated()` para essa chave).

## Modelo de Dados

Duas opções para a coluna de perfil no `users`:

- **A (simples, recomendada):** adicionar coluna `perfil` do tipo string diretamente em `users`, com cast para `PerfilEnum`. Descartar/simplificar a tabela `perfis_usuarios` — só faz sentido ter tabela separada se os perfis fossem dinâmicos, o que não é o caso.
- **B (mantém a estrutura existente):** manter `perfis_usuarios` como tabela de referência e usar `users.perfil_id`. O `User` expõe `perfil` como accessor que converte o código armazenado em `PerfilEnum`.

O design.md adota a **opção A** pela simplicidade, dado que os perfis são fechados no domínio. Uma migration adicional consolida `users.perfil` e remove/mantém a tabela `perfis_usuarios` conforme decisão do time.

## Tratamento de Erros

- **401** — sem token, token inválido ou usuário desativado.
- **403** — Atendente tentando acessar `/api/v1/usuarios/*` (exceto `auth/me`), ou Administrador tentando se auto-desativar.
- Ambos formatados como `{"message": "..."}` pelo handler central (Spec 3).

## Estratégia de Testes

- **Feature — Autenticação:** login com credenciais válidas/inválidas, usuário desativado, logout, `me`.
- **Feature — Rotas protegidas:** acesso a `/api/v1/clientes` sem token retorna 401.
- **Feature — Perfil Atendente:** acessa todos os endpoints operacionais (200); recebe 403 em qualquer `/api/v1/usuarios/*`.
- **Feature — Perfil Administrador:** acessa todos os endpoints, incluindo gerenciamento de usuários.
- **Feature — Auto-bloqueio:** Administrador tentando desativar a si mesmo → 422.
- **Feature — Segurança do `usuario_id`:** criação de OS com `usuario_id` diferente do autenticado grava o `Auth::id()`.
