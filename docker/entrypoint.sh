#!/bin/sh
set -e

# Ir al directorio de trabajo
cd /var/www/html
# Permisos 777 recursivos
echo "Entrypoint: Seteando permisos 777 en storage y bootstrap/cache..."
chmod -R 777 storage bootstrap/cache

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
php artisan storage:link

# Ejecutar el comando principal (supervisord)
exec "$@"
