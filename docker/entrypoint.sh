#!/bin/sh
set -e

cd /var/www/html

if [ "$DB_CONNECTION" != "sqlite" ]; then
    echo "Entrypoint: Verificando conexión a la DB en ${DB_HOST:-db}:3306..."
    php -r "
        \$host = '${DB_HOST:-db}';
        \$port = 3306;
        \$maxTries = 10;
        \$tries = 0;
        while (!@fsockopen(\$host, \$port) && \$tries < \$maxTries) {
            echo 'Entrypoint: DB no lista, esperando 2s...\n';
            sleep(2);
            \$tries++;
        }
    "
fi

if [ "$APP_ENV" = "local" ]; then
    echo "Entrypoint: Modo DESARROLLO detectado"
    chmod -R 777 storage bootstrap/cache

    [ ! -d vendor ] && composer install --no-interaction
    [ ! -d node_modules ] && command -v pnpm >/dev/null && pnpm install
    [ -d public/build ] && rm -rf public/build

    php artisan key:generate 
    php artisan optimize:clear
    php artisan storage:link
    php artisan filament:assets
    

    if [ "$DB_CONNECTION" = "sqlite" ]; then
        SQLITE_PATH="${DB_DATABASE:-/var/www/html/database/database.sqlite}"
        if [ ! -f "$SQLITE_PATH" ]; then
            echo "Entrypoint: Creando SQLite en $SQLITE_PATH"
            mkdir -p "$(dirname "$SQLITE_PATH")"
            touch "$SQLITE_PATH"
            chmod 666 "$SQLITE_PATH"
            chmod 777 "$(dirname "$SQLITE_PATH")"
        fi
    else
        php artisan migrate --force
    fi
else
    echo "Entrypoint: Modo PRODUCCIÓN detectado"
    php artisan optimize
fi

exec "$@"
