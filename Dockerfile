# ----------------------------------------------------
# ETAPA 1: BASE (Debian Slim con PHP 8.4)
# ----------------------------------------------------
FROM docker.io/library/php:8.4-fpm AS base

ENV COMPOSER_ALLOW_SUPERUSER=1

RUN apt-get update && apt-get install -y --no-install-recommends \
    libpng-dev libjpeg-dev libfreetype6-dev libzip-dev \
    curl nginx supervisor unzip libicu-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install sockets pdo pdo_mysql pcntl zip intl gd opcache bcmath exif \
    && pecl install redis && docker-php-ext-enable redis \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

COPY --from=docker.io/library/composer:2.8.5 /usr/bin/composer /usr/local/bin/composer

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
FROM node:24-slim AS node_builder
RUN corepack enable && corepack prepare pnpm@latest --activate
WORKDIR /var/www/html
COPY --from=build /var/www/html ./
RUN pnpm install --frozen-lockfile
RUN pnpm run build

# ----------------------------------------------------
# ETAPA FINAL UNIFICADA
# ----------------------------------------------------
FROM base AS prod
WORKDIR /var/www/html

COPY --from=build --chown=www-data:www-data /var/www/html /var/www/html
COPY --from=node_builder --chown=www-data:www-data /var/www/html/public/build ./public/build

COPY docker/php.ini /usr/local/etc/php/conf.d/app.ini

COPY docker/php-fpm.conf /usr/local/etc/php-fpm.d/www.conf

RUN rm -f /etc/nginx/sites-enabled/default /etc/nginx/sites-available/default
COPY docker/nginx.conf /etc/nginx/sites-available/optica.conf
RUN ln -s /etc/nginx/sites-available/optica.conf /etc/nginx/sites-enabled/optica.conf

COPY docker/supervisord.conf /etc/supervisor/conf.d/supervisord.conf

RUN chmod -R 775 /var/www/html/storage && chmod -R 775 /var/www/html/bootstrap/cache

EXPOSE 80

CMD ["/usr/bin/supervisord", "-n", "-c", "/etc/supervisor/conf.d/supervisord.conf"]

# ----------------------------------------------------
# WORKER FINAL STAGE
# ----------------------------------------------------
FROM base AS worker
WORKDIR /var/www/html

COPY --from=build --chown=www-data:www-data /var/www/html /var/www/html
COPY --from=node_builder --chown=www-data:www-data /var/www/html/public/build ./public/build

COPY docker/php.ini /usr/local/etc/php/conf.d/app.ini

COPY docker/supervisord-worker.conf /etc/supervisor/conf.d/supervisord.conf

RUN chmod -R 775 /var/www/html/storage && chmod -R 775 /var/www/html/bootstrap/cache

CMD ["/usr/bin/supervisord", "-n", "-c", "/etc/supervisor/conf.d/supervisord.conf"]

# ----------------------------------------------------
# DEVELOPMENT FINAL STAGE
# ----------------------------------------------------
FROM base AS dev
WORKDIR /var/www/html

RUN apt-get update && apt-get install -y --no-install-recommends \
    nodejs npm mariadb-client bash openssh-client \
    # Librerías de Chromium que sacamos de la base:
    libnss3 libnspr4 libatk1.0-0 libatk-bridge2.0-0 libcups2 libdrm2 \
    libxkbcommon0 libxcomposite1 libxdamage1 libxext6 libxfixes3 \
    libxrandr2 libgbm1 libasound2 libpango-1.0-0 libcairo2 \
    && npm install -g corepack && corepack enable \
    && corepack prepare pnpm@latest --activate \
    && pnpm install playwright \
    && npx playwright install --with-deps chromium \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

COPY --from=build --chown=www-data:www-data /var/www/html /var/www/html
COPY --from=node_builder --chown=www-data:www-data /var/www/html/public/build ./public/build

COPY docker/php-dev.ini /usr/local/etc/php/conf.d/app.ini

COPY docker/php-fpm-dev.conf /usr/local/etc/php-fpm.d/www.conf

RUN rm -f /etc/nginx/sites-enabled/default /etc/nginx/sites-available/default
COPY docker/nginx.conf /etc/nginx/sites-available/optica.conf
RUN ln -s /etc/nginx/sites-available/optica.conf /etc/nginx/sites-enabled/optica.conf

COPY docker/supervisord-dev.conf /etc/supervisor/conf.d/supervisord.conf

RUN chmod -R 775 /var/www/html/storage && chmod -R 775 /var/www/html/bootstrap/cache

EXPOSE 80 5173

CMD ["/usr/bin/supervisord", "-n", "-c", "/etc/supervisor/conf.d/supervisord.conf"]
