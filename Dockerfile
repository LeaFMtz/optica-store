# Stage 1: Base Runtime
FROM docker.io/library/php:8.4-fpm-alpine AS runtime

RUN apk add --no-cache \
    nginx \
    supervisor \
    libpng-dev \
    libjpeg-turbo-dev \
    libwebp-dev \
    freetype-dev \
    icu-dev \
    libzip-dev \
    zip \
    unzip \
    oniguruma-dev \
    libxml2-dev \
    bash

RUN docker-php-ext-configure gd --with-freetype --with-jpeg --with-webp \
    && docker-php-ext-install -j$(nproc) \
    pdo_mysql \
    gd \
    intl \
    zip \
    opcache \
    bcmath \
    pcntl \
    mbstring \
    exif

ARG WITH_DEV_DEPS=false

COPY docker/php.ini /usr/local/etc/php/conf.d/app.ini
COPY docker/php-dev.ini /usr/local/etc/php/conf.d/app.ini.dev

COPY docker/php-fpm.conf /usr/local/etc/php-fpm.d/www.conf
COPY docker/php-fpm-dev.conf /usr/local/etc/php-fpm.d/www-dev.conf

COPY docker/supervisord.conf /etc/supervisor/supervisord.conf
COPY docker/supervisord-dev.conf /etc/supervisor/supervisord-dev.conf

# POR DEFECTO LAS CONFIGURACIONES BASICAS , LES JURO Q ME PARECIO ESTO LO MAS LIMPIO ARREGLEN
# CN GUSTAVO DEL PASADO
RUN if [ "$WITH_DEV_DEPS" = "true" ] ; then \
        rm /usr/local/etc/php/conf.d/app.ini; \
        mv /usr/local/etc/php/conf.d/app.ini.dev /usr/local/etc/php/conf.d/app.ini; \
        rm /usr/local/etc/php-fpm.d/www.conf; \
        mv /usr/local/etc/php-fpm.d/www-dev.conf /usr/local/etc/php-fpm.d/www.conf; \
        rm -f /etc/supervisor/supervisord.conf && \
        mv /etc/supervisor/supervisord-dev.conf /etc/supervisor/supervisord.conf; \
    fi

COPY docker/nginx.conf /etc/nginx/nginx.conf

WORKDIR /var/www/html

# Stage 2: Build
FROM runtime AS build
ARG WITH_DEV_DEPS=false

COPY --from=docker.io/library/composer:2.9.5 /usr/bin/composer /usr/bin/composer
COPY --from=docker.io/library/node:24.14.0-alpine /usr/local/bin/node /usr/local/bin/node
COPY --from=docker.io/library/node:24.14.0-alpine /usr/local/lib/node_modules /usr/local/lib/node_modules

RUN ln -s /usr/local/bin/node /usr/local/bin/nodejs \
    && ln -s /usr/local/lib/node_modules/npm/bin/npm-cli.js /usr/local/bin/npm \
    && ln -s /usr/local/lib/node_modules/npm/bin/npx-cli.js /usr/local/bin/npx \
    && npm install -g corepack \
    && corepack enable pnpm

# Copiamos primero dependencias para aprovechar cache
COPY composer.json composer.lock* package.json pnpm-lock.yaml* ./

RUN if [ "$WITH_DEV_DEPS" = "true" ] ; then \
        apk add --no-cache git openssh ; \
        composer install --no-interaction ; \
        pnpm install ; \
    else \
        composer install --no-dev --optimize-autoloader --no-interaction ; \
        pnpm install --frozen-lockfile ; \
    fi

# Copiamos el resto del código
COPY . .

# Compilación de assets si no es dev
RUN if [ "$WITH_DEV_DEPS" = "false" ] ; then \
        pnpm run build && rm -rf node_modules ; \
    fi

# Stage 3: Final
FROM runtime AS production
ARG WITH_DEV_DEPS=false

COPY --from=build --chown=www-data:www-data /var/www/html /var/www/html

RUN if [ "$WITH_DEV_DEPS" = "true" ] ; then \
        apk add --no-cache nodejs npm git openssh && \
        npm install -g corepack && corepack enable pnpm; \
    fi

COPY --from=build /usr/bin/composer /usr/local/bin/composer_temp
RUN if [ "$WITH_DEV_DEPS" = "true" ] ; then \
        mv /usr/local/bin/composer_temp /usr/local/bin/composer; \
    else \
        rm /usr/local/bin/composer_temp; \
    fi


RUN mkdir -p /var/www/html/storage/framework/{cache,sessions,views} \
    && mkdir -p /var/www/html/storage/logs \
    && mkdir -p /var/www/html/bootstrap/cache \
    && chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache

RUN chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache

COPY docker/entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh

EXPOSE 80 5173

ENTRYPOINT ["entrypoint.sh"]
CMD ["/usr/bin/supervisord", "-c", "/etc/supervisor/supervisord.conf"]
