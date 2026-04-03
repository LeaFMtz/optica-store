# Optica Store

Tienda en línea de productos ópticos construida con Laravel y Lunar PHP.

## Requisitos

- Sistema operativo compatible (Linux recomendado)
- Podman o Docker
- Podman Compose o Docker Compose

## Instalación con dev-env.sh

El proyecto incluye un script interactivo `dev-env.sh` que automatiza toda la configuración necesaria:

### Configuración Inicial

```bash
# 1. Hacer ejecutable el script (si es necesario)
chmod +x dev-env.sh

# 2. Ejecutar el script de configuración
./dev-env.sh
```

El script se encargará de:
- Detectar e instalar dependencias necesarias (openssl, curl, mkcert, nss-tools)
- Configurar mkcert y generar certificados SSL para el dominio `ecomm.localhost`
- Crear los archivos `cert.pem` y `key.pem` en el directorio `docker/ssl/`
- Levantar los contenedores con Nginx como reverse proxy con SSL
- Crear el archivo `.env` si no existe con una clave de aplicación generada

### Argumentos de Build (Dockerfile)

| Argumento | Descripción | Valores |
|-----------|-------------|---------|
| `WITH_DEV_DEPS` | Instala herramientas de desarrollo en el contenedor | `true` (desarrollo), `false` (producción) |

### Puertos Expuestos

| Servicio | Puerto | Descripción |
|----------|--------|-------------|
| App | 443 80 | Servidor PHP-FPM + Nginx (con SSL) |
| Vite | 5173 | Hot reload para desarrollo |


### Instalación de Lunar

Después de que el script `dev-env.sh` haya finalizado exitosamente y los contenedores estén corriendo, es necesario instalar Lunar:

**Importante**: Esto es solo para cuando la base de datos no esta inicializada, si tienen un dump , descartar esta parte para no correr riesgos innecesarios. En versiones futuras implementare
una verificacion automatica de datos para una provision segura.

```bash
# Acceder al contenedor de la aplicación
docker-compose exec app bash

# Ejecutar la instalación básica de Lunar
php artisan lunar:install
```

**Importante**: Tenga cuidado al ejecutar `lunar:install` ya que sobrescribirá ciertas configuraciones existentes. Revise los cambios que propone antes de confirmar.


### Acceso

- **Tienda**: https://ecomm.localhost
- **Panel Lunar**: https://ecomm.localhost/lunar


## Despliegue Manual (Alternativa a dev-env.sh)

Si por alguna razón no desea utilizar el script `dev-env.sh`, puede desplegar el proyecto manualmente siguiendo estos pasos:

### 1. Preparación del Entorno

```bash
# Instalar dependencias necesarias
# En sistemas basados en Debian/Ubuntu:
sudo apt update && sudo apt install -y openssl curl libnss3-tools

# En sistemas basados en Fedora/RHEL:
sudo dnf install -y openssl curl nss-tools

# Instalar mkcert (consulte https://github.com/FiloSottile/mkcert para instrucciones específicas de su sistema)
```

### 2. Generación de Certificados SSL

**Nota: SSL es obligatorio para el correcto funcionamiento de la aplicación debido a:**
- Requisitos de pasarelas de pago que exigen HTTPS para webhooks y callbacks
- Acceso a la cámara y otros dispositivos del navegador, que solo funciona en contextos seguros (HTTPS) o localhost
- Cookies seguras y prevención de ataques de intermediario

```bash
# Configurar mkcert como autoridad de confianza local
mkcert -install

# Crear directorio para certificados
mkdir -p docker/ssl

# Generar certificados para los dominios necesarios
mkcert -cert-file docker/ssl/cert.pem -key-file docker/ssl/key.pem ecomm.localhost localhost 127.0.0.1 ::1
```

### 3. Configuración de Variables de Entorno

```bash
# Copiar archivo de ejemplo si no existe
if [ ! -f .env ]; then
    cp .env.example .env
    # Generar clave de aplicación
    RANDOM_KEY=$(openssl rand -base64 32)
    sed -i "s|APP_KEY=.*|APP_KEY=base64:$RANDOM_KEY|g" .env
fi

# Verificar que las variables necesarias estén configuradas
# (revisar .env.example para referencia)
```

### 4. Levantar los Contenedores

```bash
# Detener cualquier instancia anterior
docker-compose down

# Construir y levantar los contenedores
docker-compose up -d --build
```

### 5. Verificación

```bash
# Esperar a que los servicios estén listos
sleep 5

# Verificar que Nginx esté funcionando correctamente con SSL
docker-compose logs nginx | grep "emerg" && echo "Error en Nginx detectado" || echo "Nginx funcionando correctamente"

# Debería poder acceder a https://ecomm.localhost en su navegador
# (puede que necesite aceptar la advertencia de certificado autofirmado la primera vez)
```

### 6. Instalación de Lunar

```bash
# Acceder al contenedor de la aplicación
docker-compose exec app bash

# Ejecutar la instalación básica de Lunar
php artisan lunar:install
```

**Importante**: Al igual que con el método automático, tenga cuidado al ejecutar `lunar:install` ya que sobrescribirá ciertas configuraciones existentes. Revise los cambios que propone antes de confirmar.

## Consideraciones de Producción

Para entornos de producción, se recomienda:
1. Utilizar certificados SSL válidos emitidos por una autoridad de confianza reconocida (Let's Encrypt, etc.)
2. Configurar variables de entorno apropiadas para producción (desactivar debug, etc.)
3. Implementar estrategias de backup y recuperación
4. Configurar monitoreo y logging adecuados
5. Utilizar un orquestador como Kubernetes o Docker Swarm para alta disponibilidad

*Este es un proyecto en construcción. Las funcionalidades listadas están en fase de planificación.*
