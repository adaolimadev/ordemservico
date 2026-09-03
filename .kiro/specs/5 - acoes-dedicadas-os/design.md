# Design — Ações Dedicadas da OS

## Visão Geral

Três ações passam a ter endpoints próprios: `alterarStatus`, `concluir` e `cancelar`. Cada uma tem seu Form Request e seu DTO (via Spec 4). O método `update` genérico é removido; se houver necessidade de editar `descricao`/`prioridade` de uma OS não terminal, isso vira uma ação futura fora do escopo desta spec.

## Rotas

```php
// routes/api.php
Route::middleware('auth:sanctum')->prefix('v1')->group(function () {
    Route::apiResource('ordens-servico', OrdemServicoController::class)
        ->parameters(['ordens-servico' => 'ordemServico'])
        ->except(['update', 'destroy']);

    Route::patch('ordens-servico/{ordemServico}/status',   [OrdemServicoController::class, 'alterarStatus']);
    Route::post ('ordens-servico/{ordemServico}/concluir', [OrdemServicoController::class, 'concluir']);
    Route::post ('ordens-servico/{ordemServico}/cancelar', [OrdemServicoController::class, 'cancelar']);
});
```

## Controllers

```php
public function alterarStatus(AlterarStatusOrdemServicoRequest $request, OrdemServico $ordemServico)
{
    $os = $this->osService->alterarStatus($ordemServico, $request->toDto());
    return new OrdemServicoResource($os);
}

public function concluir(ConcluirOrdemServicoRequest $request, OrdemServico $ordemServico)
{
    $os = $this->osService->concluir($ordemServico, $request->toDto());
    return new OrdemServicoResource($os);
}

public function cancelar(CancelarOrdemServicoRequest $request, OrdemServico $ordemServico)
{
    $os = $this->osService->cancelar($ordemServico, $request->toDto());
    return new OrdemServicoResource($os);
}
```

## Form Requests

- `AlterarStatusOrdemServicoRequest`: `status` obrigatório; **rejeitar** valores `CONCLUIDA` e `CANCELADA` (usar `Rule::notIn`) e orientar uso das rotas específicas.
- `ConcluirOrdemServicoRequest`: `diagnostico` obrigatório e `string|min:3`.
- `CancelarOrdemServicoRequest`: `motivo` obrigatório.

## Service

Métodos públicos:
- `alterarStatus(OrdemServico $os, AlterarStatusOrdemServicoDTO $dto): OrdemServico`
- `concluir(OrdemServico $os, ConcluirOrdemServicoDTO $dto): OrdemServico`
- `cancelar(OrdemServico $os, CancelarOrdemServicoDTO $dto): OrdemServico`

Todos delegam à máquina de estados (Spec 1). `concluir` grava `diagnostico` e `data_fechamento`. `cancelar` grava `motivo` no histórico e `data_fechamento` na OS.

## `data_abertura` explícita

```php
OrdemServico::create([
    // ...
    'data_abertura' => now(),
]);
```

E na migration da OS, `data_abertura` continua com default para compatibilidade, mas o Service não confia nele.

## Migração e compatibilidade

- Remover método `update` do `OrdemServicoController`.
- Manter a antiga rota `POST /ordens-servico/{id}/cancelar` funcionando (a nova é o mesmo verbo/URL) durante o refactor.
- Adicionar entrada no CHANGELOG do projeto documentando a quebra.

## Testes

- Feature para cada ação:
  - `AlterarStatusTest`: aceita transições válidas, rejeita CONCLUIDA/CANCELADA por essa rota.
  - `ConcluirOrdemServicoTest`: exige diagnóstico; falha se status atual não é EM_EXECUCAO.
  - `CancelarOrdemServicoTest`: exige motivo; falha se terminal.
- Feature: `data_abertura` populada no `store` (assert exato ao segundo).
- Route: verifica que `PUT /ordens-servico/{id}` retorna 405.
