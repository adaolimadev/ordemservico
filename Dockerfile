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

# -----------------------------------------------------------------------------
# Cria o usuário "laravel" com UID/GID 1000, que bate com o usuário do host
# (adaolimadev, uid=1000). Isso garante que arquivos gerados dentro do
# container (artisan make:*, migrations, storage, etc.) tenham a mesma
# propriedade do usuário WSL, evitando conflitos de permissão no volume montado.
# -----------------------------------------------------------------------------
RUN groupadd --gid 1000 laravel \
    && useradd --uid 1000 --gid laravel --shell /bin/bash --create-home laravel

# Configura o PHP-FPM para rodar os workers como o usuário laravel.
# O processo master continua como root (necessário para abrir o socket/porta),
# mas todas as requisições PHP são processadas pelo usuário laravel.
RUN sed -i 's/user = www-data/user = laravel/g'   /usr/local/etc/php-fpm.d/www.conf \
    && sed -i 's/group = www-data/group = laravel/g' /usr/local/etc/php-fpm.d/www.conf

# Define o diretório de trabalho
WORKDIR /var/www

# Copia os arquivos do projeto com a propriedade correta
COPY --chown=laravel:laravel . .

# Instala as dependências do Laravel como o usuário laravel
RUN su -s /bin/sh laravel -c "composer install --optimize-autoloader"

# Garante que o diretório de storage e cache sejam graváveis
RUN chown -R laravel:laravel /var/www/storage /var/www/bootstrap/cache

# Copia o entrypoint que corrige permissões em runtime
COPY docker/entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh

EXPOSE 9000
ENTRYPOINT ["/usr/local/bin/entrypoint.sh"]
CMD ["php-fpm"]