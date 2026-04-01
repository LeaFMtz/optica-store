# Optica Store

Tienda en línea de productos ópticos construida con Laravel y Lunar PHP.

## Requisitos

- PHP 8.4+
- Docker y Docker Compose
- Node.js 24.x (para desarrollo)
- pnpm

## Instalación con Docker

### Configuración Inicial

```bash
# 1. Copiar archivo de entorno
cp .env.example .env

# 2. Iniciar contenedores
docker-compose up -d --build

# 3. Acceder al contenedor
docker-compose exec app bash

# 4. Generar clave de aplicación
php artisan key:generate

# 5. Ejecutar migraciones
php artisan migrate

# 6. Crear enlace simbólico para storage
php artisan storage:link
```

### Variables de Entorno (docker-compose.yml)

| Variable | Descripción | Valor por defecto |
|----------|-------------|-------------------|
| `WITH_DEV_DEPS` | Instala dependencias de desarrollo (git, npm, pnpm) | `true` |

### Argumentos de Build (Dockerfile)

| Argumento | Descripción | Valores |
|-----------|-------------|---------|
| `WITH_DEV_DEPS` | Instala herramientas de desarrollo en el contenedor | `true` (desarrollo), `false` (producción) |

### Puertos Expuestos

| Servicio | Puerto | Descripción |
|----------|--------|-------------|
| App | 8000 | Servidor PHP-FPM + Nginx |
| Vite | 5173 | Hot reload para desarrollo |

### Acceso

- **Tienda**: http://localhost:8000
- **Panel Lunar**: http://localhost:8000/lunar
  - Usuario: `admin@lunarphp.io`
  - Contraseña: `password`

### Comandos Útiles

```bash
# Reiniciar contenedores
docker-compose restart

# Ver logs
docker-compose logs -f app

# Ejecutar comandos artisan
docker-compose exec app php artisan [comando]

# Instalar dependencias manualmente
docker-compose exec app composer install
docker-compose exec app pnpm install
```
