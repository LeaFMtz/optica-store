# Optica Store

Tiienda en línea de productos ópticos construida con Laravel y Lunar PHP.

## Requisitos

- Docker o Podman
- Docker Compose o Podman Compose

## Instalación

### 1. Preparar entorno

```bash
# Copiar archivo de variables de entorno
cp .env.example .env
```

### 2. Levantar contenedores

```bash
# Construir y levantar servicios
docker compose up -d --build
```

### 3. Generar APP_KEY (si no está generada)

```bash
# Acceder al contenedor
docker compose exec app bash

# Generar clave de aplicación
php artisan key:generate
```

### Argumentos de Build (Dockerfile)

| Argumento | Descripción | Valores |
|-----------|-------------|---------|
| `WITH_DEV_DEPS` | Instala herramientas de desarrollo en el contenedor | `true` (desarrollo), `false` (producción) |

### Puertos Expuestos

| Servicio | Puerto | Descripción |
|----------|--------|-------------|
| App | 8080 | Servidor PHP-FPM + Nginx |
| Vite | 5173 | Hot reload para desarrollo |
| MySQL | 3306 | Base de datos |

### Instalación de Lunar

Después de que los contenedores estén corriendo, es necesario instalar Lunar:

**Importante**: Esto es solo para cuando la base de datos no esta inicializada. Si tienen un dump, descartar esta parte.

```bash
# Acceder al contenedor de la aplicación
docker compose exec app bash

# Ejecutar la instalación básica de Lunar
php artisan migrate

# Ejecutar seeding de DB
php artisan db:seed

# Crear administrado esto es interfactivo
php artisan lunar:create-admin
```

### Acceso

- **Tienda**: http://localhost:8080
- **Panel Lunar**: http://localhost:8080/panel

*Este es un proyecto en construcción.*
