# Requisitos — DTOs entre HTTP e Camada de Aplicação

## Introdução

Hoje o `OrdemServicoService` recebe `array $dados` cru, dependendo de chaves mágicas (`$dados['cliente_id']`, `$dados['equipamentos']`) e sem tipagem. A seção 13 do escopo pede o uso de DTOs `readonly` para desacoplar o HTTP do domínio. Esta spec introduz DTOs por caso de uso e faz o Form Request convertê-los.

## Requisitos

### Requisito 1 — DTOs por caso de uso

**User Story:** Como desenvolvedor do domínio, quero receber objetos tipados nos Services, para não depender do formato HTTP.

#### Critérios de Aceitação
1. QUANDO um Service recebe entrada de um caso de uso ENTÃO ele DEVE receber um DTO `final readonly` com propriedades públicas tipadas.
2. QUANDO a spec estiver aplicada ENTÃO os DTOs iniciais DEVEM ser:
   - `CriarOrdemServicoDTO`
   - `AlterarStatusOrdemServicoDTO`
   - `CancelarOrdemServicoDTO`
   - `CriarClienteDTO` / `AtualizarClienteDTO`
   - `CriarEquipamentoDTO` / `AtualizarEquipamentoDTO`

### Requisito 2 — Conversão a partir do Form Request

**User Story:** Como desenvolvedor, quero centralizar a conversão de HTTP para DTO no Form Request.

#### Critérios de Aceitação
1. QUANDO um Form Request expõe `toDto()` ENTÃO o Controller DEVE apenas invocá-lo e repassar ao Service.
2. QUANDO os dados são inválidos ENTÃO o `validate` do Form Request DEVE falhar antes da criação do DTO.
3. QUANDO `usuario_id` é necessário no DTO ENTÃO ele DEVE vir de `Auth::id()` (não do payload), integrando com a Spec 2.

### Requisito 3 — Enums tipados nos DTOs

**User Story:** Como desenvolvedor, quero que os DTOs carreguem Enums (não strings) para prioridade e status.

#### Critérios de Aceitação
1. QUANDO `CriarOrdemServicoDTO` é construído ENTÃO `prioridade` DEVE ser `PrioridadeEnum`.
2. QUANDO `AlterarStatusOrdemServicoDTO` é construído ENTÃO `novoStatus` DEVE ser `StatusOSEnum`.

### Requisito 4 — Services deixam de aceitar arrays

**User Story:** Como reviewer, quero garantir que o padrão foi adotado.

#### Critérios de Aceitação
1. QUANDO um Service é lido após a spec ENTÃO ele NÃO DEVE aceitar `array $dados` como parâmetro público.
2. QUANDO uma assinatura pública recebe entrada ENTÃO ela DEVE receber DTO ou tipos escalares nomeados (`int $usuarioId`).
