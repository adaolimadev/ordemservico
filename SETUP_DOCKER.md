# Guia de Configuração e Execução do Projeto (Docker + Laravel 12 + PostgreSQL)

Este guia descreve os passos necessários para configurar e rodar este projeto Laravel 12 em qualquer nova máquina utilizando Docker e Docker Compose.

---

## 📋 Pré-requisitos

Antes de começar, certifique-se de ter instalado no sistema destinatário:
- **Git**
- **Docker Engine** (v20.10 ou superior)
- **Docker Compose** (plugin `docker compose` v2.0+)

> **Nota:** Não é necessário ter o PHP, Composer ou PostgreSQL instalados diretamente na máquina hospedeira, pois todos esses serviços rodam de forma isolada dentro dos containers Docker.

---

## 🚀 Passo a Passo (Após Clonar o Projeto)

### 1. Clonar o Repositório
Abra o terminal e clone o repositório no diretório de sua preferência:

```bash
git clone <URL_DO_REPOSITORIO> ordem-servico
cd ordem-servico
```

---

### 2. Configurar o Arquivo de Ambiente (`.env`)
Copie o arquivo de exemplo de ambiente `.env.example` para criar o seu `.env` local:

```bash
cp .env.example .env
```

Abra o arquivo `.env` e verifique se as configurações de conexão com o banco de dados PostgreSQL correspondem ao serviço definido no Docker:

```env
DB_CONNECTION=pgsql
DB_HOST=db
DB_PORT=5432
DB_DATABASE=ordemservico
DB_USERNAME=postgres
DB_PASSWORD=secretpassword
```

> **Atenção:** O `DB_HOST` deve permanecer como **`db`**, pois este é o nome do serviço do PostgreSQL dentro da rede interna do Docker.

---

### 3. Construir e Subir os Containers
Execute o comando a seguir para construir as imagens (caso seja a primeira execução) e iniciar os serviços em segundo plano:

```bash
docker compose up -d --build
```

Isso iniciará os três containers:
- **`laravel_app`**: PHP 8.3-FPM + extensões (`pdo_pgsql`, etc.)
- **`laravel_nginx`**: Servidor Web Nginx
- **`laravel_db`**: PostgreSQL 16

---

### 4. Instalar as Dependências do PHP (Composer)
Instale todos os pacotes das dependências do Laravel dentro do container da aplicação:

```bash
docker compose exec app composer install
```

---

### 5. Gerar a Chave de Criptografia do Laravel (`APP_KEY`)
Gere uma nova chave da aplicação para garantir o funcionamento do ambiente:

```bash
docker compose exec app php artisan key:generate
```

---

### 6. Ajustar Permissões de Pastas e Limpar Caches
Garanta que as pastas de armazenamento e cache possuam as permissões corretas para gravação de logs, sessões e visões Blade compiladas:

```bash
docker compose exec app mkdir -p storage/framework/views storage/framework/cache/data storage/framework/sessions storage/logs bootstrap/cache
docker compose exec app chmod -R 777 storage bootstrap/cache
docker compose exec app php artisan config:clear
```

---

### 7. Executar as Migrações e Seeders do Banco de Dados
Crie todas as tabelas no banco de dados PostgreSQL recém-criado:

```bash
docker compose exec app php artisan migrate
```

*(Opcional) Caso o projeto possua dados iniciais de teste (Seeders):*
```bash
docker compose exec app php artisan db:seed
```

---

## 🌐 Endereços e Acesso

- **Aplicação Web:** [http://localhost:8000](http://localhost:8000)

### Conexão ao Banco de Dados por Clientes Externos (DBeaver, pgAdmin, VS Code)
Para conectar um gerenciador de banco de dados instalado na sua máquina física ao PostgreSQL do container:

| Parâmetro | Valor |
| :--- | :--- |
| **Host** | `localhost` ou `127.0.0.1` |
| **Porta Externa** | `5433` |
| **Banco de Dados** | `ordemservico` |
| **Usuário** | `postgres` |
| **Senha** | `secretpassword` |

---

## 🛠️ Comandos Úteis do Dia a Dia

### Parar os containers
```bash
docker compose stop
```

### Iniciar os containers parados
```bash
docker compose start
```

### Derrubar e remover os containers
```bash
docker compose down
```

### Visualizar os logs em tempo real
```bash
docker compose logs -f
```

### Executar comandos Artisan
```bash
docker compose exec app php artisan <comando>
```

### Executar comandos Composer
```bash
docker compose exec app composer <comando>
```

---

## ⚡ Resumo Rápido dos Comandos (Cheat Sheet)

```bash
git clone <URL_DO_REPOSITORIO> ordem-servico
cd ordem-servico
cp .env.example .env
docker compose up -d --build
docker compose exec app composer install
docker compose exec app php artisan key:generate
docker compose exec app chmod -R 777 storage bootstrap/cache
docker compose exec app php artisan migrate
```
