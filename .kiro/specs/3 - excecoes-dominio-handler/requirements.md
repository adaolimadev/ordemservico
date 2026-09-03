# Requisitos — Exceções de Domínio e Handler Centralizado

## Introdução

Hoje o `OrdemServicoService` lança `\Exception` genérica e os controllers capturam com try/catch, retornando 422 manualmente. Isso mistura responsabilidades (controller sabe demais sobre erros de domínio) e não permite tipagem/discriminação dos erros. Esta spec introduz exceções específicas por regra de negócio e centraliza o render em `bootstrap/app.php`, seguindo a seção 15 do escopo.

## Requisitos

### Requisito 1 — Hierarquia de exceções de domínio

**User Story:** Como desenvolvedor, quero exceções específicas para cada violação de regra de negócio, para tratar cada caso adequadamente.

#### Critérios de Aceitação
1. QUANDO uma regra de domínio é violada ENTÃO o sistema DEVE lançar uma exceção que estende `App\Exceptions\Domain\DomainException` (base própria, herdando de `\DomainException`).
2. QUANDO listadas, as exceções específicas iniciais DEVEM incluir:
   - `TransicaoStatusInvalidaException`
   - `ClienteNaoEncontradoException`
   - `EquipamentoNaoEncontradoException`
   - `EquipamentoNaoPertenceAoClienteException`
   - `EquipamentoInativoException`
   - `EquipamentoComOsAtivaException`
   - `OrdemServicoNaoEncontradaException`
   - `OrdemServicoJaCanceladaException`
   - `OrdemServicoJaConcluidaException`
   - `IntegracaoErpException`

### Requisito 2 — Render centralizado

**User Story:** Como consumidor da API, quero respostas de erro consistentes em toda a aplicação.

#### Critérios de Aceitação
1. QUANDO uma exceção de domínio é lançada e não capturada ENTÃO o handler DEVE convertê-la em resposta JSON com `{ "message": "...", "code": "..." }`.
2. QUANDO uma requisição espera JSON (rotas `/api/*` ou header `Accept: application/json`) ENTÃO a resposta de erro DEVE ser JSON.
3. QUANDO a exceção é `IntegracaoErpException` ENTÃO o status HTTP DEVE ser 502.
4. QUANDO a exceção é `*NaoEncontrado(a)Exception` ENTÃO o status DEVE ser 404.
5. QUANDO a exceção é de violação de regra (transição, equipamento não pertence, já cancelada etc.) ENTÃO o status DEVE ser 422.
6. QUANDO um `ValidationException` (Form Request) ocorre ENTÃO o formato de resposta DEVE permanecer o padrão do Laravel.

### Requisito 3 — Controllers livres de try/catch

**User Story:** Como desenvolvedor, quero controllers enxutos que apenas delegam.

#### Critérios de Aceitação
1. QUANDO um controller de API é lido ENTÃO ele NÃO DEVE conter `try/catch` para exceções de domínio.
2. QUANDO o Service lança uma exceção de domínio ENTÃO o handler central DEVE responder no formato correto, sem intervenção do controller.

### Requisito 4 — Logging e observabilidade

**User Story:** Como time de operações, quero rastrear os erros de integração e domínio.

#### Critérios de Aceitação
1. QUANDO uma `IntegracaoErpException` é lançada ENTÃO o sistema DEVE registrar log de nível `error` com contexto (endpoint, payload resumido, resposta).
2. QUANDO uma exceção de domínio de negócio (422) ocorre ENTÃO o sistema DEVE registrar log de nível `warning`, sem stack trace ruidoso.
3. QUANDO ocorre erro não previsto (`Throwable` inesperado) em requisição JSON ENTÃO a resposta DEVE ser 500 com mensagem genérica, sem expor stack trace em produção.
