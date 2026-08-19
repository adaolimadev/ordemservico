# Estudo de Caso — Plataforma de Gestão de Ordens de Serviço

## 1. Contexto

Uma empresa do setor industrial possui um sistema ERP legado responsável por armazenar informações operacionais e cadastrais, como clientes, equipamentos, produtos e pedidos.

Apesar de o ERP continuar sendo utilizado como sistema central, algumas atividades do dia a dia são realizadas por processos manuais ou por sistemas internos pouco integrados. Isso dificulta o acompanhamento das solicitações de manutenção, aumenta a possibilidade de erros e reduz a visibilidade das equipes responsáveis pelo atendimento.

A Fundação foi contratada para desenvolver uma aplicação web que permita centralizar o processo de abertura e acompanhamento de Ordens de Serviço (OS), mantendo integração com o ERP existente.

A solução deverá ser desenvolvida considerando manutenção evolutiva, integração com sistemas legados, segurança, testes, performance, escalabilidade e facilidade de manutenção.

---

## 2. Problematização

Atualmente, a empresa enfrenta dificuldades no gerenciamento das solicitações de manutenção.

O processo pode envolver diferentes áreas:

1. O cliente solicita uma manutenção.
2. Um funcionário consulta os dados do cliente no ERP.
3. O equipamento relacionado precisa ser identificado.
4. Uma Ordem de Serviço é criada.
5. Técnicos acompanham e atualizam o atendimento.
6. Ao concluir o serviço, informações precisam ser refletidas no ERP.
7. Gestores precisam acompanhar o volume e o status das ordens.

O problema é que essas etapas não estão necessariamente centralizadas em uma única aplicação.

Isso pode provocar problemas como:

- duplicidade de cadastro;
- informações desatualizadas;
- dificuldade para localizar uma Ordem de Serviço;
- ausência de rastreabilidade;
- atualização manual de informações no ERP;
- erros durante a comunicação entre sistemas;
- demora para identificar ordens pendentes;
- dificuldade para gerar indicadores;
- dependência de processos manuais;
- dificuldade de manutenção dos sistemas existentes.

### Problema central

> Como desenvolver uma aplicação web que permita gerenciar Ordens de Serviço de maneira centralizada, confiável e escalável, integrando-se ao ERP legado sem substituir o sistema existente?

---

## 3. Objetivo

Desenvolver uma aplicação web para gerenciamento do ciclo de vida das Ordens de Serviço, permitindo que usuários autorizados:

- cadastrem e consultem clientes;
- consultem equipamentos;
- criem Ordens de Serviço;
- acompanhem o andamento das OS;
- alterem o status das OS;
- registrem informações do atendimento;
- concluam ou cancelem ordens;
- consultem histórico;
- integrem informações relevantes ao ERP;
- acompanhem indicadores operacionais.

A aplicação deverá funcionar como uma camada de negócio e operação sobre os sistemas existentes, evitando a necessidade de substituir o ERP.

---

## 4. Escopo funcional

### 4.1 Usuários

O sistema deverá permitir:

- cadastro de usuários;
- consulta;
- edição;
- ativação e desativação;
- controle básico de acesso.

Possíveis perfis:

- Administrador;
- Atendente;
- Técnico;
- Gestor.

---

### 4.2 Clientes

O sistema deverá permitir:

- cadastrar clientes;
- consultar clientes;
- editar dados;
- ativar ou desativar clientes;
- consultar informações provenientes do ERP.

Dados básicos:

```text
Cliente
-------
id
razaoSocial
nomeFantasia
cnpj
email
telefone
situacao
createdAt
updatedAt
```

---

### 4.3 Equipamentos

Cada cliente poderá possuir diversos equipamentos.

Dados:

```text
Equipamento
-----------
id
clienteId
numeroSerie
descricao
modelo
situacao
createdAt
updatedAt
```

Regras:

- equipamento deve estar associado a um cliente;
- número de série deve ser único;
- equipamento inativo não pode receber novas Ordens de Serviço;
- o equipamento informado em uma OS deve pertencer ao cliente selecionado.

---

### 4.4 Ordens de Serviço

A Ordem de Serviço representa a principal entidade do sistema.

Dados:

```text
OrdemServico
------------
id
numero
clienteId
equipamentoId
usuarioId
descricao
prioridade
status
dataAbertura
dataFechamento
createdAt
updatedAt
```

Prioridades:

```text
BAIXA
MEDIA
ALTA
CRITICA
```

Status:

```text
ABERTA
EM_ANALISE
EM_EXECUCAO
AGUARDANDO_CLIENTE
CONCLUIDA
CANCELADA
```

---

## 5. Ciclo de vida da Ordem de Serviço

A OS deverá seguir um fluxo controlado:

```text
ABERTA
  │
  ▼
EM_ANALISE
  │
  ▼
EM_EXECUCAO
  │
  ├──────────────► AGUARDANDO_CLIENTE
  │                       │
  │                       ▼
  │                 EM_EXECUCAO
  │
  ▼
CONCLUIDA
```

Também poderá ocorrer:

```text
ABERTA ─────────► CANCELADA
EM_ANALISE ─────► CANCELADA
```

O sistema deverá impedir transições inválidas.

Por exemplo:

- uma OS concluída não pode voltar para aberta;
- uma OS cancelada não pode entrar em execução;
- uma OS já concluída não pode ser cancelada;
- uma OS em execução pode aguardar retorno do cliente;
- uma OS aguardando cliente pode retornar para execução.

---

## 6. Integração com ERP

O ERP existente utiliza SQL Server e continuará sendo utilizado pela empresa.

A nova aplicação utilizará PostgreSQL como banco principal.

A arquitetura será:

```text
                    ┌────────────────────┐
                    │      Vue 3         │
                    │   Web Application  │
                    └─────────┬──────────┘
                              │
                              │ HTTP/REST
                              ▼
                    ┌────────────────────┐
                    │    Laravel 12      │
                    │       API          │
                    └──────┬───────┬─────┘
                           │       │
                    ┌──────┘       └──────────┐
                    ▼                         ▼
             ┌─────────────┐          ┌─────────────┐
             │ PostgreSQL  │          │ SQL Server  │
             │             │          │     ERP     │
             └─────────────┘          └─────────────┘
```

O PostgreSQL será responsável pelos dados pertencentes à nova aplicação.

O SQL Server será utilizado para consultar ou atualizar informações que permanecem sob responsabilidade do ERP.

### Princípio importante

A aplicação não deverá duplicar indiscriminadamente todos os dados do ERP.

Cada sistema deve possuir responsabilidade clara sobre os dados que administra.

---

## 7. Cenário de integração

Ao cadastrar uma Ordem de Serviço, o sistema deverá validar:

1. se o cliente existe;
2. se o cliente está ativo;
3. se o equipamento existe;
4. se o equipamento pertence ao cliente;
5. se o equipamento está ativo.

Dependendo da regra de negócio, parte dessas informações poderá ser obtida diretamente do ERP.

Exemplo:

```text
Usuário
   │
   │ cria OS
   ▼
Laravel
   │
   ├── valida cliente
   │
   ├── valida equipamento
   │
   └── grava OS
          │
          ▼
      PostgreSQL
```

Após a conclusão da OS:

```text
OS CONCLUÍDA
      │
      ▼
Job assíncrono
      │
      ▼
Integração ERP
      │
      ▼
SQL Server
```

O processamento de integração não deverá necessariamente bloquear a resposta HTTP ao usuário.

---

## 8. Integração com API externa

O sistema também deverá possuir pelo menos uma integração com uma API externa.

Um exemplo é a consulta de endereço por CEP.

Fluxo:

```text
Vue
 │
 │ CEP
 ▼
Laravel
 │
 ▼
API externa
 │
 ▼
Endereço
 │
 ▼
Laravel
 │
 ▼
Vue
```

A integração deverá considerar:

- timeout;
- tratamento de erros;
- validação da resposta;
- logs;
- retry quando aplicável;
- possibilidade de cache.

---

## 9. Requisitos não funcionais

### 9.1 Segurança

A aplicação deverá considerar:

- autenticação;
- autorização;
- validação de entrada;
- proteção contra acesso indevido;
- controle de permissões;
- não exposição de informações sensíveis;
- tratamento adequado de erros;
- proteção das credenciais de banco e APIs.

---

### 9.2 Performance

A aplicação deverá evitar:

- consultas desnecessárias;
- N+1 queries;
- processamento pesado durante requisições HTTP;
- chamadas externas sem timeout;
- consultas sem paginação.

Poderão ser utilizados:

- paginação;
- cache;
- índices;
- eager loading;
- filas;
- jobs assíncronos;
- Redis.

---

### 9.3 Escalabilidade

A aplicação deverá ser preparada para crescimento de:

- usuários;
- clientes;
- equipamentos;
- Ordens de Serviço;
- integrações;
- volume de consultas.

A solução deverá evitar dependências desnecessárias de estado local da aplicação para facilitar a execução de múltiplas instâncias.

---

### 9.4 Observabilidade

Deverão existir logs para eventos importantes, principalmente:

- erros;
- falhas de integração;
- alterações de status;
- processamento de jobs;
- comunicação com APIs externas.

---

## 10. Arquitetura proposta

Será adotada uma abordagem de **Modular Monolith**, utilizando princípios de Clean Architecture e separação de responsabilidades de forma pragmática.

Não será adotada arquitetura de microserviços inicialmente, pois o domínio não apresenta complexidade suficiente para justificar o custo operacional dessa abordagem.

Estrutura conceitual:

```text
HTTP
 │
 ▼
Controllers
 │
 ▼
Application
 │
 ▼
Domain
 │
 ▼
Infrastructure
 │
 ├── PostgreSQL
 ├── SQL Server / ERP
 └── APIs externas
```

---

## 11. Organização do backend

Estrutura sugerida:

```text
app/
├── Domain/
│   ├── Cliente/
│   ├── Equipamento/
│   └── OrdemServico/
│
├── Application/
│   ├── Cliente/
│   ├── Equipamento/
│   └── OrdemServico/
│
├── Infrastructure/
│   ├── ERP/
│   ├── ExternalApi/
│   └── Persistence/
│
└── Http/
    ├── Controllers/
    ├── Requests/
    └── Resources/
```

A separação tem como objetivo evitar que regras de negócio fiquem concentradas nos Controllers ou diretamente nos Models.

---

## 12. API REST

Principais endpoints:

### Clientes

```http
GET    /api/clientes
GET    /api/clientes/{id}
POST   /api/clientes
PUT    /api/clientes/{id}
PATCH  /api/clientes/{id}/situacao
```

### Equipamentos

```http
GET    /api/equipamentos
GET    /api/equipamentos/{id}
POST   /api/equipamentos
PUT    /api/equipamentos/{id}
```

### Ordens de Serviço

```http
GET    /api/ordens-servico
GET    /api/ordens-servico/{id}
POST   /api/ordens-servico
PATCH  /api/ordens-servico/{id}/status
POST   /api/ordens-servico/{id}/concluir
POST   /api/ordens-servico/{id}/cancelar
```

---

## 13. DTOs

A aplicação deverá evitar acoplamento direto entre os objetos HTTP e as regras de negócio.

Exemplo conceitual:

```text
HTTP Request
     │
     ▼
FormRequest
     │
     ▼
DTO
     │
     ▼
Application Service
     │
     ▼
Domain
```

Exemplo:

```php
final readonly class CriarOrdemServicoDTO
{
    public function __construct(
        public int $clienteId,
        public int $equipamentoId,
        public int $usuarioId,
        public string $descricao,
        public string $prioridade,
    ) {}
}
```

---

## 14. Validação

A validação de entrada deverá ser feita através de Form Requests.

Exemplo conceitual:

```php
class StoreOrdemServicoRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'cliente_id' => ['required', 'integer'],
            'equipamento_id' => ['required', 'integer'],
            'descricao' => ['required', 'string', 'max:2000'],
            'prioridade' => [
                'required',
                Rule::in([
                    'BAIXA',
                    'MEDIA',
                    'ALTA',
                    'CRITICA'
                ])
            ],
        ];
    }
}
```

A validação de formato e a validação de regra de negócio deverão possuir responsabilidades distintas.

---

## 15. Tratamento de exceções

A API deverá possuir tratamento centralizado de exceções.

Exemplo:

```json
{
    "message": "O equipamento informado não pertence ao cliente."
}
```

Possíveis erros:

```text
ClienteNaoEncontradoException
EquipamentoNaoEncontradoException
EquipamentoNaoPertenceAoClienteException
OrdemServicoNaoEncontradaException
TransicaoStatusInvalidaException
IntegracaoErpException
```

---

## 16. Testes

A solução deverá possuir diferentes níveis de testes.

### Unitários

Testar regras de negócio isoladamente.

Exemplos:

```text
- deve permitir criar uma OS
- não deve permitir equipamento de outro cliente
- não deve concluir uma OS cancelada
- não deve cancelar uma OS concluída
- deve permitir retorno de AGUARDANDO_CLIENTE para EM_EXECUCAO
```

### Testes de integração / Feature

Testar endpoints completos:

```text
POST /api/ordens-servico
GET /api/ordens-servico
PATCH /api/ordens-servico/{id}/status
```

### Testes de integração externa

Testar a comunicação com:

- ERP;
- API de CEP.

---

## 17. Frontend

O frontend será desenvolvido utilizando:

- Vue 3;
- TypeScript;
- Vite;
- Vue Router;
- Pinia;
- Axios.

Principais telas:

```text
Dashboard
│
├── Clientes
│   ├── Listagem
│   ├── Cadastro
│   └── Edição
│
├── Equipamentos
│   ├── Listagem
│   └── Cadastro
│
└── Ordens de Serviço
    ├── Listagem
    ├── Cadastro
    ├── Detalhes
    └── Histórico
```

---

## 18. Dashboard

O sistema deverá apresentar indicadores como:

```text
┌───────────────────────────────────────┐
│ Ordens Abertas       42               │
│ Em Execução          18               │
│ Aguardando Cliente   07               │
│ Concluídas no mês    126              │
└───────────────────────────────────────┘
```

Poderão ser adicionados gráficos de:

- OS por status;
- OS por prioridade;
- OS por período;
- tempo médio de atendimento;
- quantidade de OS por cliente.

---

## 19. Docker

O ambiente deverá ser executado através de containers.

Serviços:

```text
docker-compose.yml
│
├── app
├── nginx
├── postgres
├── sqlserver
└── redis
```

Objetivos:

- padronizar o ambiente;
- facilitar onboarding;
- reduzir problemas de configuração;
- reproduzir o ambiente de desenvolvimento;
- facilitar testes e CI.

---

## 20. CI/CD

O projeto deverá possuir pipeline automatizado.

Fluxo:

```text
Git Push
   │
   ▼
CI
 │
 ├── Composer
 ├── Lint
 ├── Static Analysis
 ├── Tests
 ├── Build
 └── Docker
       │
       ▼
    Deploy
```

Como a empresa utiliza Bitbucket, o pipeline poderá posteriormente ser implementado utilizando Bitbucket Pipelines.

---

## 21. Documentação

O projeto deverá possuir documentação suficiente para que outro desenvolvedor consiga executar e compreender a aplicação.

O README deverá conter:

- objetivo do projeto;
- contexto;
- arquitetura;
- tecnologias;
- requisitos;
- configuração;
- execução com Docker;
- execução dos testes;
- documentação da API;
- estrutura do projeto;
- decisões arquiteturais.

Também poderá ser utilizada documentação OpenAPI/Swagger.

---

## 22. Decisões arquiteturais

Algumas decisões deverão ser justificadas durante o desenvolvimento.

### Por que Modular Monolith?

Porque o sistema possui um domínio relativamente pequeno e não existe, inicialmente, uma necessidade clara de separar os módulos em serviços independentes.

Microserviços adicionariam:

- maior complexidade operacional;
- observabilidade distribuída;
- comunicação entre serviços;
- gerenciamento de deploys;
- maior custo de infraestrutura.

A arquitetura deverá permitir uma futura separação caso o domínio cresça.

### Por que PostgreSQL?

Será utilizado como banco principal da nova aplicação por oferecer recursos robustos, bom desempenho e integração madura com o Laravel.

### Por que SQL Server?

Porque o ERP existente utiliza SQL Server e não será substituído pela nova aplicação.

### Por que filas?

Operações externas que não precisam bloquear a resposta ao usuário poderão ser processadas de maneira assíncrona.

---

## 23. Resultado esperado

Ao final do projeto, teremos uma aplicação capaz de:

```text
                 ┌─────────────────┐
                 │     Usuário     │
                 └────────┬────────┘
                          │
                          ▼
                 ┌─────────────────┐
                 │     Vue 3       │
                 └────────┬────────┘
                          │ REST
                          ▼
                 ┌─────────────────┐
                 │   Laravel 12    │
                 │       API       │
                 └───────┬─┬───────┘
                         │ │
              ┌──────────┘ └───────────┐
              ▼                        ▼
       ┌─────────────┐          ┌─────────────┐
       │ PostgreSQL  │          │ SQL Server  │
       │ Aplicação   │          │ ERP legado  │
       └─────────────┘          └─────────────┘
                         │
                         ▼
                  ┌─────────────┐
                  │ APIs externas│
                  └─────────────┘
```

O projeto deverá demonstrar conhecimentos de:

- PHP orientado a objetos;
- Laravel;
- arquitetura MVC;
- APIs REST;
- PostgreSQL;
- SQL Server;
- integração com ERP;
- integração com APIs externas;
- Vue;
- Docker;
- Linux;
- testes;
- CI/CD;
- documentação;
- performance;
- escalabilidade;
- boas práticas de desenvolvimento.

---

## 24. Objetivo do estudo de caso para preparação profissional

Mais do que construir um CRUD, este projeto deverá servir como uma simulação de um sistema que poderia existir em um ambiente corporativo real.

Durante o desenvolvimento deverão ser discutidas as decisões técnicas e seus respectivos trade-offs.

O objetivo é conseguir responder perguntas como:

- Por que essa arquitetura?
- Por que não microserviços?
- Onde deve ficar uma regra de negócio?
- Quando utilizar DTO?
- Quando utilizar Repository?
- Como tratar uma falha no ERP?
- O que acontece se a API externa estiver indisponível?
- Como evitar N+1 queries?
- Como processar operações demoradas?
- Como testar uma integração externa?
- Como proteger a API?
- Como escalar a aplicação?
- Como fazer deploy?
- Como monitorar erros?
- Como documentar uma API?
- Como manter compatibilidade com um ERP legado?

Dessa forma, o estudo de caso não será apenas um exercício de programação, mas uma simulação completa do processo de **análise, arquitetura, desenvolvimento, integração, testes, implantação e manutenção de uma aplicação web corporativa**.
