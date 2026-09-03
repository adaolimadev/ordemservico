# Design — Integridade Equipamento-Cliente

## Visão Geral

A validação de "não trocar cliente com OS ativa" fica no `UpdateEquipamentoRequest` (via closure) para retorno consistente com o padrão de validação Laravel, com apoio de um método utilitário no Model. A unicidade de `numero_serie` no update passa a ignorar o próprio registro.

## Componentes e Interfaces

### `Equipamento` (Model)

```php
public function possuiOsAtiva(): bool
{
    return $this->itensOs()
        ->join('ordens_servico', 'ordem_servico_itens.ordem_servico_id', '=', 'ordens_servico.id')
        ->whereNotIn('ordens_servico.status', [
            StatusOSEnum::CONCLUIDA->value,
            StatusOSEnum::CANCELADA->value,
        ])
        ->exists();
}

public function itensOs(): HasMany
{
    return $this->hasMany(OrdemServicoItem::class);
}
```

### `UpdateEquipamentoRequest`

```php
public function rules(): array
{
    $equipamento = $this->route('equipamento'); // Model bindado

    return [
        'cliente_id' => [
            'sometimes','integer',
            Rule::exists('clientes','id')->where('situacao', true),
            function ($attr, $value, Closure $fail) use ($equipamento) {
                if ((int) $value !== (int) $equipamento->cliente_id
                    && $equipamento->possuiOsAtiva()) {
                    $fail('Não é possível trocar o cliente de um equipamento com OS ativa.');
                }
            },
        ],
        'tipo_equipamento_id' => ['sometimes','integer','exists:tipos_equipamentos,id'],
        'numero_serie' => [
            'sometimes','string',
            Rule::unique('equipamentos','numero_serie')->ignore($equipamento->id),
        ],
        'marca'     => ['sometimes','string','max:255'],
        'descricao' => ['sometimes','string'],
        'situacao'  => ['sometimes','boolean'],
    ];
}
```

Alternativa considerada: proibir por completo a troca de `cliente_id` no update. Descartada porque há casos legítimos (correção de cadastro após o cliente inicial estar zerado de OS).

### Filtro de "equipamentos disponíveis para OS"

Endpoint sugerido para consumo do frontend:

```
GET /api/v1/clientes/{cliente}/equipamentos?disponiveis=1
```

Retorna equipamentos com `situacao = true` e sem OS ativa.

## Modelo de Dados

Sem alterações estruturais. Índices sugeridos (integram com Spec 7):
- `equipamentos(cliente_id)`
- `equipamentos(situacao)`
- `ordens_servico(status)` — para acelerar `possuiOsAtiva`

## Tratamento de Erros

- Falha de validação por troca de cliente com OS ativa: 422 com formato padrão Laravel (`errors.cliente_id`).
- Se preferir usar exceção de domínio ao invés de validação, disparar `EquipamentoComOsAtivaException` no Service; escolhido aqui manter em Form Request por simetria com as demais validações.

## Estratégia de Testes

- **Feature**: `UpdateEquipamentoTest::nao_permite_trocar_cliente_com_os_ativa`.
- **Feature**: `UpdateEquipamentoTest::permite_trocar_cliente_sem_os_ativa`.
- **Feature**: `UpdateEquipamentoTest::numero_serie_unico_ignora_proprio_registro`.
- **Feature (regressão)**: `StoreOrdemServicoTest::rejeita_equipamentos_de_outro_cliente`, `::rejeita_equipamento_inativo`.
