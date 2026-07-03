FROM node:24-slim AS node_builder

RUN apt-get update && apt-get install -y python3 make g++ && rm -rf /var/lib/apt/lists/*

WORKDIR /app

RUN corepack enable && corepack prepare pnpm@10.32.1 --activate

COPY . . 

RUN pnpm install --frozen-lockfile

RUN pnpm run build

# -------------------------------

FROM gustavoadriang/php-8.4-fpm-nginx-supervisor-slim:latest AS base
WORKDIR /var/www/html

# ----------------------------------------------------
# ETAPA 1: BUILD (PHP PROD)
# ----------------------------------------------------
FROM base AS build
WORKDIR /var/www/html
COPY composer.* ./
RUN composer install --no-dev --optimize-autoloader --no-interaction --no-scripts
COPY . .
RUN mkdir -p storage/app storage/framework/cache storage/framework/sessions storage/framework/views logs

# ----------------------------------------------------
# BUILD PROD
# ----------------------------------------------------
FROM base AS prod
WORKDIR /var/www/html

COPY --from=build --chown=www-data:www-data /var/www/html /var/www/html
COPY --from=node_builder /app/public/build /var/www/html/public/build/

COPY docker/php.ini /etc/php/8.4/fpm/conf.d/99-app.ini

COPY docker/php-fpm.conf /etc/php/8.4/fpm/pool.d/www.conf
COPY docker/php-fpm-main.conf /etc/php/8.4/fpm/php-fpm.conf

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

RUN apt-get update && apt-get install -y --no-install-recommends \
    mariadb-client \
    && rm -rf /var/lib/apt/lists/*

COPY --from=build --chown=www-data:www-data /var/www/html /var/www/html
COPY --from=node_builder /app/public/build /var/www/html/public/build/

COPY docker/php-worker.ini /etc/php/8.4/cli/conf.d/99-app.ini

COPY docker/supervisord-worker.conf /etc/supervisor/conf.d/supervisord.conf

RUN chmod -R 775 /var/www/html/storage && chmod -R 775 /var/www/html/bootstrap/cache

CMD ["/usr/bin/supervisord", "-n", "-c", "/etc/supervisor/conf.d/supervisord.conf"]

# ----------------------------------------------------
# DEVELOPMENT FINAL STAGE
# ----------------------------------------------------
FROM base AS dev
WORKDIR /var/www/html

RUN apt-get update && apt-get install -y curl ca-certificates gnupg \
    && mkdir -p /etc/apt/keyrings \
    && curl -fsSL https://deb.nodesource.com/gpgkey/nodesource-repo.gpg.key | gpg --dearmor -o /etc/apt/keyrings/nodesource.gpg \
    && echo "deb [signed-by=/etc/apt/keyrings/nodesource.gpg] https://deb.nodesource.com/node_24.x nodistro main" > /etc/apt/sources.list.d/nodesource.list \
    && apt-get update \
    && apt-get install -y nodejs \
    && node -v

RUN apt-get install -y --no-install-recommends \
    mariadb-client bash openssh-client php8.4-xdebug php8.4-sqlite3 \
    libnss3 libnspr4 libatk1.0-0 libatk-bridge2.0-0 libcups2 libdrm2 \
    libxkbcommon0 libxcomposite1 libxdamage1 libxext6 libxfixes3 \
    libxrandr2 libgbm1 libasound2 libpango-1.0-0 libcairo2 \
    ripgrep fd-find sd sqlite3 \
    && npm install -g corepack && corepack enable \
    && corepack prepare pnpm@10.32.1 --activate \
    && pnpm install playwright \
    && npx playwright install --with-deps chromium \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

COPY --from=build --chown=www-data:www-data /var/www/html /var/www/html

COPY docker/php-dev.ini /etc/php/8.4/fpm/conf.d/99-app.ini
COPY docker/xdebug.ini /etc/php/8.4/fpm/conf.d/99-xdebug.ini
COPY docker/xdebug.ini /etc/php/8.4/cli/conf.d/99-xdebug.ini

COPY docker/php-fpm-dev.conf /etc/php/8.4/fpm/pool.d/www.conf
COPY docker/php-fpm-main.conf /etc/php/8.4/fpm/php-fpm.conf

RUN rm -f /etc/nginx/sites-enabled/default /etc/nginx/sites-available/default
COPY docker/nginx.conf /etc/nginx/sites-available/optica.conf
RUN ln -s /etc/nginx/sites-available/optica.conf /etc/nginx/sites-enabled/optica.conf

COPY docker/supervisord-dev.conf /etc/supervisor/conf.d/supervisord.conf

RUN chmod -R 775 /var/www/html/storage && chmod -R 775 /var/www/html/bootstrap/cache

EXPOSE 80 5173

CMD ["/usr/bin/supervisord", "-n", "-c", "/etc/supervisor/conf.d/supervisord.conf"]
