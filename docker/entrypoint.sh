#!/bin/sh
set -e

# -----------------------------------------------------------------------------
# Entrypoint do container laravel_app
#
# Roda como root para garantir que storage e bootstrap/cache sejam graváveis
# pelo usuário laravel (uid=1000), mesmo que o volume tenha arquivos criados
# por outros processos. O PHP-FPM master process roda como root mas delega
# os workers para o usuário laravel via configuração em www.conf.
# Comandos como `docker exec laravel_app php artisan ...` herdam o usuário
# do processo principal. Para rodar artisan como laravel, use:
#   docker exec --user laravel laravel_app php artisan ...
# -----------------------------------------------------------------------------

# Corrige permissões do storage e bootstrap/cache em runtime
chown -R laravel:laravel /var/www/storage /var/www/bootstrap/cache

# Executa o PHP-FPM como root (master process); workers rodam como laravel via www.conf
exec "$@"
