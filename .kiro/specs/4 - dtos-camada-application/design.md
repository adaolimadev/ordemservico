# Design — DTOs entre HTTP e Camada de Aplicação

## Visão Geral

DTOs ficam em `app/Application/<Modulo>/DTO/`. São `final readonly class`, apenas com propriedades públicas tipadas e construtor. Cada Form Request implementa `toDto()`. O Controller vira: valida → converte → chama Service.

## Componentes e Interfaces

### Estrutura de pastas

```
app/
└── Application/
    ├── OrdemServico/
    │   └── DTO/
    │       ├── CriarOrdemServicoDTO.php
    │       ├── AlterarStatusOrdemServicoDTO.php
    │       └── CancelarOrdemServicoDTO.php
    ├── Cliente/
    │   └── DTO/
    │       ├── CriarClienteDTO.php
    │       └── AtualizarClienteDTO.php
    └── Equipamento/
        └── DTO/
            ├── CriarEquipamentoDTO.php
            └── AtualizarEquipamentoDTO.php
```

### Exemplo — `CriarOrdemServicoDTO`

```php
final readonly class CriarOrdemServicoDTO
{
    /** @param array<int, int> $equipamentoIds */
    public function __construct(
        public int $clienteId,
        public int $usuarioId,
        public string $descricao,
        public PrioridadeEnum $prioridade,
        public array $equipamentoIds,
    ) {}
}
```

### Form Request → DTO

```php
class StoreOrdemServicoRequest extends FormRequest
{
    public function rules(): array { /* ... */ }

    public function toDto(): CriarOrdemServicoDTO
    {
        $data = $this->validated();
        return new CriarOrdemServicoDTO(
            clienteId:      (int) $data['cliente_id'],
            usuarioId:      (int) $this->user()->id, // Auth, não payload
            descricao:      $data['descricao'],
            prioridade:     PrioridadeEnum::from($data['prioridade']),
            equipamentoIds: array_map('intval', $data['equipamentos']),
        );
    }
}
```

### Service refatorado

```php
public function criarOrdemServico(CriarOrdemServicoDTO $dto): OrdemServico
{
    return DB::transaction(function () use ($dto) {
        $os = OrdemServico::create([
            'numero'        => $this->gerarNumeroOS(),
            'cliente_id'    => $dto->clienteId,
            'usuario_id'    => $dto->usuarioId,
            'descricao'     => $dto->descricao,
            'prioridade'    => $dto->prioridade,
            'status'        => StatusOSEnum::ABERTA,
            'data_abertura' => now(),
        ]);
        $os->itens()->createMany(
            array_map(fn ($id) => ['equipamento_id' => $id], $dto->equipamentoIds)
        );
        $os->historicos()->create([
            'usuario_id' => $dto->usuarioId,
            'status'     => StatusOSEnum::ABERTA,
        ]);
        return $os->load(['cliente', 'itens.equipamento', 'historicos']);
    });
}
```

### Controller final

```php
public function store(StoreOrdemServicoRequest $request)
{
    $os = $this->osService->criarOrdemServico($request->toDto());
    return new OrdemServicoResource($os);
}
```

## Convenções

- Sem setters, sem métodos de mutação, sem lógica.
- Não usar Eloquent Model como parâmetro de DTO (usar IDs).
- Enums (não strings) para campos com domínio fechado.
- Se um DTO envolver arrays de itens complexos, criar sub-DTOs (`ItemOrdemServicoDTO`).

## Estratégia de Testes

- **Unitário**: testes rápidos de `toDto()` para cada Form Request (`assertEquals` com fixture).
- **Feature** existente cobre integração ponta a ponta; nada a duplicar.
