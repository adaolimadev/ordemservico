# Design — Máquina de Estados da OS

## Visão Geral

A máquina de estados fica **no próprio Enum** `StatusOSEnum`, mantendo o domínio coeso e permitindo testes unitários puros. O `OrdemServicoService` delega a decisão ao Enum e apenas orquestra a persistência dentro da transação. Uma exceção de domínio dedicada sinaliza transições inválidas.

## Arquitetura

```
Controller (HTTP)
   │
   ▼
UpdateOrdemServicoRequest  (valida formato)
   │
   ▼
OrdemServicoService::alterarStatus
   │
   ├── StatusOSEnum::podeTransitarPara?  ──► false ──► TransicaoStatusInvalidaException
   │
   ▼
DB::transaction
   ├── update(os.status, os.data_fechamento?)
   └── historicos_os.create
```

## Componentes e Interfaces

### `App\Enums\StatusOSEnum` (estendido)

```php
enum StatusOSEnum: string
{
    case ABERTA = 'ABERTA';
    case EM_ANALISE = 'EM_ANALISE';
    case EM_EXECUCAO = 'EM_EXECUCAO';
    case AGUARDANDO_CLIENTE = 'AGUARDANDO_CLIENTE';
    case CONCLUIDA = 'CONCLUIDA';
    case CANCELADA = 'CANCELADA';

    /** @return array<int, self> */
    public function transicoesPermitidas(): array
    {
        return match ($this) {
            self::ABERTA             => [self::EM_ANALISE, self::CANCELADA],
            self::EM_ANALISE         => [self::EM_EXECUCAO, self::CANCELADA],
            self::EM_EXECUCAO        => [self::AGUARDANDO_CLIENTE, self::CONCLUIDA],
            self::AGUARDANDO_CLIENTE => [self::EM_EXECUCAO],
            self::CONCLUIDA,
            self::CANCELADA          => [],
        };
    }

    public function podeTransitarPara(self $destino): bool
    {
        return in_array($destino, $this->transicoesPermitidas(), true);
    }

    public function ehTerminal(): bool
    {
        return $this === self::CONCLUIDA || $this === self::CANCELADA;
    }
}
```

### `App\Exceptions\Domain\TransicaoStatusInvalidaException`

Extende `DomainException`. Guarda `statusAtual` e `statusDestino` para logging.

### `OrdemServicoService::alterarStatus`

Método novo dedicado (o `atualizar` genérico será removido pela Spec 5 — "Ações dedicadas"). Enquanto essa refatoração não ocorre, o `atualizar` atual passa a delegar para `alterarStatus`.

Fluxo:
1. `if ($atual === $novo) return $os;` (no-op idempotente).
2. `if (!$atual->podeTransitarPara($novo))` → lança `TransicaoStatusInvalidaException`.
3. `DB::transaction`:
   - `update` do status; se `$novo->ehTerminal()`, seta `data_fechamento = now()`.
   - `historicos()->create(usuario_id, status)`.

## Modelo de Dados

Sem alterações estruturais. `historicos_os` já registra o novo status; a spec 8 aborda opcionalmente o `status_anterior`.

## Tratamento de Erros

- `TransicaoStatusInvalidaException` → HTTP 422 via handler central (Spec 3).
- Mensagem: `"Transição inválida: {atual} → {destino}."`

## Estratégia de Testes

- **Unitário**: `StatusOSEnumTest` percorre os 36 pares e valida cada `podeTransitarPara`.
- **Feature**: `PATCH /api/v1/ordens-servico/{id}/status` cobre:
  - transição válida devolve 200 e grava histórico;
  - transição inválida devolve 422;
  - mesmo status devolve 200 sem gravar histórico;
  - transição para terminal preenche `data_fechamento`.
