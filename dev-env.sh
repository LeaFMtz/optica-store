#!/bin/bash

# Colores para la terminal
GREEN='\033[0;32m'
BLUE='\033[0;34m'
YELLOW='\033[1;33m'
RED='\033[0;31m'
NC='\033[0m'

echo -e "${BLUE}=== Setup Interactivo: Óptica Guzmán ===${NC}"

# --- DEFINICIÓN DE VARIABLES (IMPORTANTE) ---
DOMAIN="ecomm.localhost"
SSL_PATH="./docker/ssl"
# --------------------------------------------

# 1. Función para verificar comandos
check_dep() {
    command -v "$1" &> /dev/null
}

# 2. Detectar Motor de Contenedores
if check_dep "podman"; then
    COMPOSE_CMD="podman-compose"
elif check_dep "docker"; then
    COMPOSE_CMD="docker-compose"
else
    echo -e "${RED}[X] ERROR: No se encontró Podman ni Docker.${NC}"
    exit 1
fi

# 3. Detectar SO
if [ -f /etc/fedora-release ]; then
    PKG_MGR="sudo dnf install -y"
elif [ -f /etc/debian_version ] || [ -f /etc/lsb-release ]; then
    PKG_MGR="sudo apt update && sudo apt install -y"
else
    echo -e "${RED}[X] SO no soportado.${NC}"
    exit 1
fi

# 4. Instalación de dependencias (Incluyendo nss-tools para que mkcert funcione en browsers)
# nss-tools es necesario para que mkcert pueda meter la CA en la db de Brave/Chrome
for dep in openssl curl mkcert nss-tools; do
    if ! check_dep "$dep"; then
        echo -e "${BLUE} -> Instalando dependencia: $dep...${NC}"
        $PKG_MGR "$dep"
    fi
done

# 5. Configuración de mkcert y Certificados
echo -e "${BLUE} -> Configurando Autoridad Raíz (Local CA)...${NC}"
mkcert -install # Esto hace que tu PC confíe en mkcert (pide sudo)

echo -e "${BLUE} -> Generando certificados para $DOMAIN con mkcert...${NC}"
mkdir -p "$SSL_PATH"

# Generamos los archivos con los nombres exactos que espera tu Nginx
mkcert -cert-file "$SSL_PATH/cert.pem" -key-file "$SSL_PATH/key.pem" "$DOMAIN" "localhost" "127.0.0.1" "::1"

echo -e "${GREEN}[OK] Certificados generados y confiables.${NC}"

# 6. Gestión del .env (Se mantiene igual...)
if [ ! -f .env ]; then
    echo -e "${YELLOW} -> Creando .env...${NC}"
    cp .env.example .env
    RANDOM_KEY=$(openssl rand -base64 32)
    sed -i "s|APP_KEY=.*|APP_KEY=base64:$RANDOM_KEY|g" .env
    # ... (resto de tu lógica de DB) ...
fi

# 7. Levantar entorno (REINICIO TOTAL)
echo -e "${GREEN} -> Reiniciando servicios para aplicar certificados...${NC}"

# Limpieza profunda para evitar que Nginx use certs viejos en memoria
$COMPOSE_CMD down

# Levantar
$COMPOSE_CMD up -d

# Verificación de Nginx
echo -e "${BLUE} -> Verificando estado de Nginx...${NC}"
sleep 2
if check_dep "podman"; then
    podman logs optica_nginx 2>&1 | grep "emerg" && echo -e "${RED}Error en Nginx detectado${NC}"
else
    docker logs optica_nginx 2>&1 | grep "emerg" && echo -e "${RED}Error en Nginx detectado${NC}"
fi

echo -e "${BLUE}=== Instalación Finalizada ===${NC}"
echo -e "URL: ${GREEN}https://$DOMAIN${NC}"
