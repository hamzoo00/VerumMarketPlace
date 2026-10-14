# syntax=docker/dockerfile:1

############################
# Base: shared PHP runtime
############################
FROM php:8.4-fpm-bookworm AS base

ARG UID=1000
ARG GID=1000

ENV DEBIAN_FRONTEND=noninteractive \
    COMPOSER_ALLOW_SUPERUSER=1 \
    COMPOSER_MEMORY_LIMIT=-1

# System deps:
#  - libpq-dev        -> pdo_pgsql (PostgreSQL)
#  - libicu-dev       -> intl (money/locale formatting)
#  - libzip/zip       -> composer archive handling
#  - libjpeg/png/webp -> gd, for medialibrary image conversions
#  - libexif-dev      -> exif, medialibrary reads orientation from camera photos
RUN apt-get update && apt-get install -y --no-install-recommends \
        git \
        unzip \
        zip \
        curl \
        ca-certificates \
        libpq-dev \
        libicu-dev \
        libzip-dev \
        libjpeg62-turbo-dev \
        libpng-dev \
        libwebp-dev \
        libfreetype6-dev \
        libexif-dev \
        libonig-dev \
    && rm -rf /var/lib/apt/lists/*

RUN docker-php-ext-configure gd --with-jpeg --with-webp --with-freetype \
    && docker-php-ext-configure intl \
    && docker-php-ext-install -j"$(nproc)" \
        pdo_pgsql \
        pgsql \
        intl \
        zip \
        gd \
        exif \
        bcmath \
        opcache \
        pcntl \
        sockets

# Redis (cache, queues, Horizon) is a PECL extension, not bundled with PHP
RUN pecl install redis && docker-php-ext-enable redis

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Run as the host user so files written in the bind mount stay editable from WSL
RUN if getent group "${GID}" >/dev/null; then \
        groupmod -n appuser "$(getent group "${GID}" | cut -d: -f1)"; \
    else \
        groupadd -g "${GID}" appuser; \
    fi \
    && if getent passwd "${UID}" >/dev/null; then \
        usermod -l appuser -g "${GID}" -d /home/appuser -m "$(getent passwd "${UID}" | cut -d: -f1)"; \
    else \
        useradd -u "${UID}" -g "${GID}" -m -s /bin/bash appuser; \
    fi

WORKDIR /var/www/html

############################
# Dev: bind-mounted source
############################
FROM base AS dev

COPY docker/php/php.ini /usr/local/etc/php/conf.d/zz-app.ini

# Named volumes mount as root-owned; pre-create and chown the paths that get
# volume-mounted so appuser can write to them on a fresh clone.
RUN mkdir -p /var/www/html/bootstrap/cache \
             /var/www/html/storage/framework/cache/data \
             /var/www/html/storage/framework/sessions \
             /var/www/html/storage/framework/views \
             /var/www/html/storage/logs \
    && chown -R appuser:appuser /var/www/html

USER appuser

EXPOSE 9000
CMD ["php-fpm"]

############################
# Prod: source baked in
############################
FROM base AS prod

COPY docker/php/php.ini /usr/local/etc/php/conf.d/zz-app.ini
COPY docker/php/opcache-prod.ini /usr/local/etc/php/conf.d/zz-opcache.ini

COPY --chown=appuser:appuser composer.json composer.lock ./
RUN composer install \
        --no-dev \
        --no-interaction \
        --no-progress \
        --prefer-dist \
        --no-scripts \
        --no-autoloader

COPY --chown=appuser:appuser . .

RUN composer dump-autoload --optimize --no-dev --classmap-authoritative \
    && mkdir -p storage/framework/{cache/data,sessions,testing,views} storage/logs bootstrap/cache \
    && chown -R appuser:appuser storage bootstrap/cache

USER appuser

EXPOSE 9000
CMD ["php-fpm"]
