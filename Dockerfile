# ----------------------------------------------------
# ETAPA 1: BASE (Debian Bookworm Slim + PHP 8.4 via Sury)
# ----------------------------------------------------
FROM debian:bookworm-slim AS base

ENV COMPOSER_ALLOW_SUPERUSER=1 \
    DEBIAN_FRONTEND=noninteractive

RUN mkdir -p /etc/apt/keyrings /run/php \
    && apt-get update && apt-get install -y --no-install-recommends ca-certificates curl gnupg \
    && curl -sSL https://packages.sury.org/php/apt.gpg | gpg --dearmor -o /etc/apt/keyrings/sury.gpg \
    && echo "deb [signed-by=/etc/apt/keyrings/sury.gpg] https://packages.sury.org/php bookworm main" \
       > /etc/apt/sources.list.d/sury-php.list \
    && apt-get update && apt-get install -y --no-install-recommends \
       php8.4-fpm php8.4-curl php8.4-gd php8.4-mysql php8.4-xml php8.4-zip \
       php8.4-intl php8.4-opcache php8.4-bcmath php8.4-exif \
       php8.4-sockets php8.4-redis \
       nginx supervisor unzip \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

COPY --from=docker.io/library/composer:2.9.5 /usr/bin/composer /usr/local/bin/composer

# ----------------------------------------------------
# ETAPA 2: BUILD (PHP PROD)
# ----------------------------------------------------
FROM base AS build
WORKDIR /var/www/html
COPY composer.* ./
RUN composer install --no-dev --optimize-autoloader --no-interaction --no-scripts
COPY . .
RUN mkdir -p storage/app storage/framework/cache storage/framework/sessions storage/framework/views logs

# ----------------------------------------------------
# ETAPA: NODE_BUILDER
# ----------------------------------------------------
FROM node:24-slim AS node_builder

RUN corepack enable && corepack prepare pnpm@latest --activate
WORKDIR /app

COPY package.json pnpm-lock.yaml ./
RUN pnpm install --frozen-lockfile

COPY vite.config.js tailwind.config.js postcss.config.js ./
COPY resources/ ./resources/

RUN pnpm run build

# ----------------------------------------------------
# BUILD PROD
# ----------------------------------------------------
FROM base AS prod
WORKDIR /var/www/html

COPY --from=build --chown=www-data:www-data /var/www/html /var/www/html
COPY --from=node_builder --chown=www-data:www-data /app/public/build ./public/build

COPY docker/php.ini /etc/php/8.4/fpm/conf.d/99-app.ini

COPY docker/php-fpm.conf /etc/php/8.4/fpm/pool.d/www.conf

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
COPY --from=node_builder --chown=www-data:www-data /app/public/build ./public/build

COPY docker/php.ini /etc/php/8.4/cli/conf.d/99-app.ini

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
    libnss3 libnspr4 libatk1.0-0 libatk-bridge2.0-0 libcups2 libdrm2 \
    libxkbcommon0 libxcomposite1 libxdamage1 libxext6 libxfixes3 \
    libxrandr2 libgbm1 libasound2 libpango-1.0-0 libcairo2 \
    && npm install -g corepack && corepack enable \
    && corepack prepare pnpm@latest --activate \
    && pnpm install playwright \
    && npx playwright install --with-deps chromium \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

COPY --from=build --chown=www-data:www-data /var/www/html /var/www/html
COPY --from=node_builder --chown=www-data:www-data /app/public/build ./public/build

COPY docker/php-dev.ini /etc/php/8.4/fpm/conf.d/99-app.ini

COPY docker/php-fpm-dev.conf /etc/php/8.4/fpm/pool.d/www.conf

RUN rm -f /etc/nginx/sites-enabled/default /etc/nginx/sites-available/default
COPY docker/nginx.conf /etc/nginx/sites-available/optica.conf
RUN ln -s /etc/nginx/sites-available/optica.conf /etc/nginx/sites-enabled/optica.conf

COPY docker/supervisord-dev.conf /etc/supervisor/conf.d/supervisord.conf

RUN chmod -R 775 /var/www/html/storage && chmod -R 775 /var/www/html/bootstrap/cache

EXPOSE 80 5173

CMD ["/usr/bin/supervisord", "-n", "-c", "/etc/supervisor/conf.d/supervisord.conf"]
