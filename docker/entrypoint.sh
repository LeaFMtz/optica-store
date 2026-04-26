#!/bin/sh
set -e

# Ir al directorio de trabajo
cd /var/www/html
# Permisos 777 recursivos
echo "Entrypoint: Seteando permisos 777 en storage y bootstrap/cache..."
chmod -R 777 storage bootstrap/cache

# --- NUEVA SECCIÓN: ESPERA A LA DB ---
echo "Entrypoint: Esperando a que la base de datos esté lista en ${DB_HOST:-db}:3306..."

# --- ESPERA A LA DB USANDO PHP ---
echo "Entrypoint: Esperando a la DB en ${DB_HOST:-db}:3306..."

php -r "
    \$host = '${DB_HOST:-db}';
    \$port = 3306;
    while (!@fsockopen(\$host, \$port)) {
        echo 'Entrypoint: DB no lista, esperando 2s...\n';
        sleep(2);
    }
"

echo "Entrypoint: ¡Base de datos conectada!"
echo "Entrypoint: ¡Conexión establecida con la base de datos!"

# Dependencias PHP
if [ ! -d vendor ] && command -v composer >/dev/null 2>&1; then
    echo "Entrypoint: Directorio vendor no encontrado. Ejecutando composer install..."
    composer install --no-interaction --no-progress
fi

# Dependencias JS (solo si pnpm está disponible)
if [ ! -d node_modules ] && command -v pnpm >/dev/null 2>&1; then
    echo "Entrypoint: Directorio node_modules no encontrado. Ejecutando pnpm install..."
    pnpm install
fi

# LIMPIEZA PARA MANTENER SIN CACHE Y AL DIA EL ENTORNO DE DEV
php artisan optimize:clear
php artisan migrate

if [ ! -L public/storage ] && [ ! -d public/storage ]; then
    if command -v php >/dev/null 2>&1 && [ -f artisan ]; then
        php artisan storage:link
    fi
fi

# Ejecutar el comando principal (supervisord)
exec "$@"
