# Design — Refinamentos Gerais

## Visão Geral

Cinco frentes pequenas e independentes: (1) Resources emitindo `->value` dos Enums; (2) Histórico com `status_anterior` + `motivo`; (3) Verificação e correção da dependência `laravel/pao`; (4) Documentação básica da API; (5) Padronização de idioma dos identificadores.

## Detalhes por frente

### 1. Enums nas Resources

Sem magia global (embora Laravel serialize Enums automaticamente em muitos casos, é seguro explicitar):

```php
public function toArray(Request $request): array
{
    return [
        'id'          => $this->id,
        'numero'      => $this->numero,
        'status'      => $this->status?->value,
        'prioridade'  => $this->prioridade?->value,
        // ...
    ];
}
```

### 2. Histórico com `status_anterior`

Estrutura pós-migração:

```
historicos_os
-------------
id
ordem_servico_id  (indexado)
usuario_id
status_anterior   (nullable, string)
status_novo       (string)
motivo            (nullable, text)
created_at
```

Migration:

```php
Schema::table('historicos_os', function (Blueprint $t) {
    $t->renameColumn('status', 'status_novo');
});
Schema::table('historicos_os', function (Blueprint $t) {
    $t->string('status_anterior')->nullable()->after('usuario_id');
    $t->text('motivo')->nullable()->after('status_novo');
});
```

O Model:

```php
protected $fillable = ['ordem_servico_id','usuario_id','status_anterior','status_novo','motivo'];
protected function casts(): array
{
    return [
        'status_anterior' => StatusOSEnum::class,
        'status_novo'     => StatusOSEnum::class,
    ];
}
```

Services atualizam o par a cada gravação. Cancelamento inclui `motivo` (integra com Spec 5, requisito 3).

### 3. Dependência `laravel/pao`

Ação: rodar `composer show laravel/pao`. Se retornar erro:
- Se a intenção era Pest, substituir por `pestphp/pest` (`require --dev`).
- Se foi typo de `laravel/pail` (que já existe no `require-dev`), apenas remover a duplicada.
- Atualizar `composer.lock`.

### 4. Documentação da API

Duas opções:

- **`docs/api.md`** (mais simples): tabela markdown listando endpoints, método, corpo, resposta e erros comuns.
- **`openapi.yaml`** (mais formal): gera Swagger UI depois. Recomenda-se começar com `api.md` e migrar para OpenAPI quando houver tempo.

### 5. Padronização de idioma

Regra: colunas/rotas em português `snake_case`; nomes PHP em `camelCase`. Exceções documentadas: `users.name`, `users.email` (herdados do skeleton Laravel).

## Estratégia de Testes

- Snapshot de Resource: `assertJsonStructure` + `assertJsonPath('data.status','ABERTA')`.
- Feature de histórico: criação, alteração de status e cancelamento validando o par `status_anterior/status_novo` e `motivo`.
- Composer: apenas verificação manual + build no CI (não há teste automatizado).
