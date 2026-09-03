# Design — Performance de Consultas

## Visão Geral

Introduz uma classe `OrdemServicoIndexRequest` (Form Request para GET) que valida filtros, paginação e ordenação; e um `OrdemServicoRepository`/query scope que aplica os filtros. Adiciona migration com índices e um endpoint `dashboard/indicadores`. Ativa `Model::preventLazyLoading()` em ambiente `local`/`testing` para caçar N+1 durante desenvolvimento.

## Componentes e Interfaces

### `OrdemServicoIndexRequest`

```php
public function rules(): array
{
    return [
        'status'      => ['sometimes', Rule::enum(StatusOSEnum::class)],
        'prioridade'  => ['sometimes', Rule::enum(PrioridadeEnum::class)],
        'cliente_id'  => ['sometimes','integer','exists:clientes,id'],
        'numero'      => ['sometimes','string','max:50'],
        'aberta_de'   => ['sometimes','date'],
        'aberta_ate'  => ['sometimes','date','after_or_equal:aberta_de'],
        'sort'        => ['sometimes','string', Rule::in([
            'numero','-numero','data_abertura','-data_abertura',
            'data_fechamento','-data_fechamento',
            'prioridade','-prioridade','status','-status',
        ])],
        'per_page'    => ['sometimes','integer','between:1,100'],
    ];
}
```

### Query scopes em `OrdemServico`

```php
public function scopeFiltrar(Builder $q, array $filtros): Builder
{
    return $q
        ->when($filtros['status']     ?? null, fn($q,$v) => $q->where('status',$v))
        ->when($filtros['prioridade'] ?? null, fn($q,$v) => $q->where('prioridade',$v))
        ->when($filtros['cliente_id'] ?? null, fn($q,$v) => $q->where('cliente_id',$v))
        ->when($filtros['numero']     ?? null, fn($q,$v) => $q->where('numero','like',"%{$v}%"))
        ->when($filtros['aberta_de']  ?? null, fn($q,$v) => $q->whereDate('data_abertura','>=',$v))
        ->when($filtros['aberta_ate'] ?? null, fn($q,$v) => $q->whereDate('data_abertura','<=',$v));
}
```

### Controller

```php
public function index(OrdemServicoIndexRequest $request)
{
    $sort   = $request->input('sort', '-data_abertura');
    $col    = ltrim($sort, '-');
    $dir    = str_starts_with($sort, '-') ? 'desc' : 'asc';
    $per    = (int) $request->input('per_page', 15);

    $os = OrdemServico::query()
        ->with(['cliente:id,nome_razao_social', 'responsavel:id,name'])
        ->filtrar($request->validated())
        ->orderBy($col, $dir)
        ->paginate($per);

    return OrdemServicoResource::collection($os);
}

public function show(OrdemServico $ordemServico)
{
    $ordemServico->load([
        'cliente',
        'responsavel',
        'itens.equipamento.tipoEquipamento',
        'historicos.usuario',
    ]);
    return new OrdemServicoResource($ordemServico);
}
```

### Migration de índices

```php
Schema::table('ordens_servico', function (Blueprint $t) {
    $t->index('status');
    $t->index('prioridade');
    $t->index('data_abertura');
});
Schema::table('equipamentos', function (Blueprint $t) {
    $t->index('situacao');
});
Schema::table('historicos_os', function (Blueprint $t) {
    $t->index('ordem_servico_id');
});
```

(As FKs já cobrem alguns campos; adiciona-se onde faltar após inspeção.)

### `AppServiceProvider::boot()`

```php
Model::preventLazyLoading(! app()->isProduction());
```

Isso quebra testes/dev caso surja N+1. Em produção fica desligado para segurança.

### `DashboardController`

```php
public function indicadores()
{
    $contagemStatus = OrdemServico::query()
        ->selectRaw('status, COUNT(*) as total')
        ->groupBy('status')->pluck('total','status');

    $concluidasMes = OrdemServico::query()
        ->where('status', StatusOSEnum::CONCLUIDA)
        ->whereYear('data_fechamento', now()->year)
        ->whereMonth('data_fechamento', now()->month)
        ->count();

    return response()->json([
        'por_status'         => $contagemStatus,
        'concluidas_no_mes'  => $concluidasMes,
    ]);
}
```

Opcional: `Cache::remember('dashboard:indicadores', 60, fn () => ...)`.

## Estratégia de Testes

- **Feature**: filtros combinados retornam resultados corretos.
- **Feature**: `per_page` fora do intervalo devolve 422.
- **Performance**: `assertQueryCountLessThan(N)` na listagem paginada (via helper com `DB::listen`).
- **Feature**: ativação de `preventLazyLoading` no `TestCase` para pegar N+1.
- **Feature**: `dashboard/indicadores` retorna contagens corretas com fixture.
