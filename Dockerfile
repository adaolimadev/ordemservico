FROM php:8.3-fpm

# Instala as dependências do sistema, incluindo o libpq-dev para o PostgreSQL
RUN apt-get update && apt-get install -y \
    git \
    curl \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    zip \
    unzip \
    libpq-dev

# Limpa o cache
RUN apt-get clean && rm -rf /var/lib/apt/lists/*

# Instala as extensões essenciais do PHP e o driver do PostgreSQL
RUN docker-php-ext-install pdo_pgsql pgsql mbstring exif pcntl bcmath gd

# Instala o Composer na imagem
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Define o diretório de trabalho
WORKDIR /var/www

# Copia os arquivos do projeto
COPY . .

# Instala as dependências do Laravel
RUN composer install --optimize-autoloader --no-dev