# ----------------------------------------------------
# ETAPA 1: BASE (Debian Slim con PHP 8.4)
# ----------------------------------------------------
FROM docker.io/library/php:8.4-fpm AS base

ENV COMPOSER_ALLOW_SUPERUSER=1

# En Debian usamos apt-get
RUN apt-get update && apt-get install -y --no-install-recommends \
    libpng-dev \
    libjpeg-dev \
    libfreetype6-dev \
    libzip-dev \
    curl \
    nginx \
    supervisor \
    unzip \
    libicu-dev \
    git \
    build-essential \
    autoconf \
    pkg-config \
    libnss3 libnspr4 libatk1.0-0 libatk-bridge2.0-0 libcups2 libdrm2 \
    libxkbcommon0 libxcomposite1 libxdamage1 libxext6 libxfixes3 \
    libxrandr2 libgbm1 libasound2 libpango-1.0-0 libcairo2 \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install \
        sockets \
        pdo \
        pdo_mysql \
        pcntl \
        zip \
        intl \
        gd \
        opcache \
        bcmath \
        exif \
    && pecl install redis && docker-php-ext-enable redis \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

COPY --from=docker.io/library/composer:2.8.5 /usr/bin/composer /usr/local/bin/composer

ARG WITH_DEV_DEPS=false

COPY docker/php.ini /usr/local/etc/php/conf.d/app.ini
COPY docker/php-dev.ini /usr/local/etc/php/conf.d/app.ini.dev

COPY docker/php-fpm.conf /usr/local/etc/php-fpm.d/www.conf
COPY docker/php-fpm-dev.conf /usr/local/etc/php-fpm.d/www-dev.conf

# POR DEFECTO LAS CONFIGURACIONES BASICAS , LES JURO Q ME PARECIO ESTO LO MAS LIMPIO ARREGLEN
# CN GUSTAVO DEL PASADO
RUN if [ "$WITH_DEV_DEPS" = "true" ] ; then \
        rm /usr/local/etc/php/conf.d/app.ini; \
        mv /usr/local/etc/php/conf.d/app.ini.dev /usr/local/etc/php/conf.d/app.ini; \
        rm /usr/local/etc/php-fpm.d/www.conf; \
        mv /usr/local/etc/php-fpm.d/www-dev.conf /usr/local/etc/php-fpm.d/www.conf; \
    fi
# ---------------------------------------------------p
# ETAPA 2: BUILD (PHP PROD)
# ----------------------------------------------------
FROM base AS build
WORKDIR /var/www/html
COPY composer.* ./
RUN composer install --no-dev --optimize-autoloader --no-interaction --no-scripts
COPY . .
RUN mkdir -p storage/app storage/framework/cache storage/framework/sessions storage/framework/views logs

# ----------------------------------------------------
# ETAPA: NODE_BUILDER (Compila React/Vite - Seguimos usando Alpine aquí porque es solo para compilar JS)
# ----------------------------------------------------
FROM node:20-alpine AS node_builder
RUN corepack enable && corepack prepare pnpm@latest --activate
WORKDIR /var/www/html
COPY --from=build /var/www/html ./
RUN pnpm install --frozen-lockfile
RUN pnpm run build

# ----------------------------------------------------
# ETAPA FINAL UNIFICADA
# ----------------------------------------------------
FROM base AS final
WORKDIR /var/www/html

ARG WITH_DEV_DEPS=false

# Instalación de Node y Playwright si es modo DEV
RUN if [ "$WITH_DEV_DEPS" = "true" ]; then \
    apt-get update && apt-get install -y --no-install-recommends \
        nodejs \
        npm \
        mariadb-client \
        bash \
        openssh-client && \
    npm install -g corepack && \
    corepack enable && \
    corepack prepare pnpm@latest --activate && \
    pnpm install playwright && \
    npx playwright install --with-deps chromium && \
    apt-get clean && rm -rf /var/lib/apt/lists/*; \
fi

COPY --from=build --chown=www-data:www-data /var/www/html /var/www/html
COPY --from=node_builder --chown=www-data:www-data /var/www/html/public/build ./public/build

RUN rm -f /etc/nginx/sites-enabled/default /etc/nginx/sites-available/default
COPY docker/nginx.conf /etc/nginx/sites-available/optica.conf
RUN ln -s /etc/nginx/sites-available/optica.conf /etc/nginx/sites-enabled/optica.conf

COPY docker/supervisord.conf /etc/supervisor/conf.d/supervisord-api.conf
COPY docker/supervisord-worker.conf /etc/supervisor/conf.d/supervisord-worker.conf
COPY docker/supervisord-dev.conf /etc/supervisor/conf.d/supervisord-dev.conf

RUN chmod -R 775 /var/www/html/storage && chmod -R 775 /var/www/html/bootstrap/cache

EXPOSE 80 5173

CMD ["/usr/bin/supervisord", "-n", "-c", "/etc/supervisor/conf.d/supervisord-api.conf"]
