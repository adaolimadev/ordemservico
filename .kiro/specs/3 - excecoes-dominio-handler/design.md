# Design — Exceções de Domínio e Handler Centralizado

## Visão Geral

Cria-se uma hierarquia própria de exceções em `app/Exceptions/Domain/`, com uma base abstrata que expõe `httpStatus()` e `errorCode()`. O render é registrado em `bootstrap/app.php` (Laravel 11+ style), evitando um handler class-based. Cada exceção específica sobrescreve os métodos e opcionalmente adiciona campos de contexto (ex.: `statusAtual`, `statusDestino`).

## Arquitetura

```
Service ──lança──► DomainException
                        │
                 bootstrap/app.php
                 withExceptions(...)
                        │
                        ▼
                 JSON { message, code }
```

## Componentes e Interfaces

### `App\Exceptions\Domain\DomainException` (base)

```php
abstract class DomainException extends \DomainException
{
    public function httpStatus(): int { return 422; }
    public function errorCode(): string
    {
        return Str::of(static::class)->classBasename()->replace('Exception', '')->snake()->upper();
    }
    public function context(): array { return []; }
}
```

### Exceções concretas

| Exceção                                     | HTTP | code                              |
| ------------------------------------------- | ---- | --------------------------------- |
| TransicaoStatusInvalidaException            | 422  | TRANSICAO_STATUS_INVALIDA         |
| ClienteNaoEncontradoException               | 404  | CLIENTE_NAO_ENCONTRADO            |
| EquipamentoNaoEncontradoException           | 404  | EQUIPAMENTO_NAO_ENCONTRADO        |
| EquipamentoNaoPertenceAoClienteException    | 422  | EQUIPAMENTO_NAO_PERTENCE_CLIENTE  |
| EquipamentoInativoException                 | 422  | EQUIPAMENTO_INATIVO               |
| EquipamentoComOsAtivaException              | 422  | EQUIPAMENTO_COM_OS_ATIVA          |
| OrdemServicoNaoEncontradaException          | 404  | ORDEM_SERVICO_NAO_ENCONTRADA      |
| OrdemServicoJaCanceladaException            | 422  | ORDEM_SERVICO_JA_CANCELADA        |
| OrdemServicoJaConcluidaException            | 422  | ORDEM_SERVICO_JA_CONCLUIDA        |
| IntegracaoErpException                      | 502  | INTEGRACAO_ERP                    |

### Handler central — `bootstrap/app.php`

```php
->withExceptions(function (Exceptions $exceptions): void {
    $exceptions->shouldRenderJsonWhen(
        fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
    );

    $exceptions->render(function (DomainException $e, Request $request) {
        if ($e->httpStatus() >= 500) {
            Log::error($e->getMessage(), ['ctx' => $e->context(), 'exception' => $e]);
        } else {
            Log::warning($e->getMessage(), ['ctx' => $e->context()]);
        }
        return response()->json([
            'message' => $e->getMessage(),
            'code'    => $e->errorCode(),
        ], $e->httpStatus());
    });
});
```

### Limpeza dos controllers

- `OrdemServicoController::cancelar` e `::update` deixam de usar `try/catch`.
- Os controllers apenas chamam o Service e retornam a Resource.

## Formato de resposta

**Erro de domínio (422/404/502):**
```json
{ "message": "Transição inválida: ABERTA → CONCLUIDA.", "code": "TRANSICAO_STATUS_INVALIDA" }
```

**Erro de validação (mantido do Laravel):**
```json
{ "message": "...", "errors": { "campo": ["mensagem"] } }
```

## Estratégia de Testes

- **Feature**: para cada exceção, provocar cenário no endpoint e checar status + payload.
- **Unitário**: `DomainExceptionTest` valida derivação automática de `errorCode()`.
- **Log**: teste com `Log::spy()` para confirmar níveis apropriados.
