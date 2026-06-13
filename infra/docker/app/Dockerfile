FROM node:24-alpine AS assets

WORKDIR /build

ARG VITE_REVERB_APP_KEY=gabarito360-key
ARG VITE_REVERB_HOST
ARG VITE_REVERB_PORT=80
ARG VITE_REVERB_SCHEME=http

ENV VITE_REVERB_APP_KEY=${VITE_REVERB_APP_KEY}
ENV VITE_REVERB_HOST=${VITE_REVERB_HOST}
ENV VITE_REVERB_PORT=${VITE_REVERB_PORT}
ENV VITE_REVERB_SCHEME=${VITE_REVERB_SCHEME}

COPY backend/package.json backend/package-lock.json ./
RUN npm ci

COPY backend/ ./
RUN npm run build

FROM php:8.3-fpm-bookworm

ENV DEBIAN_FRONTEND=noninteractive
ENV PATH="/opt/omr/bin:${PATH}"

RUN apt-get update \
    && apt-get install -y --no-install-recommends \
        gosu \
        libcurl4-openssl-dev \
        libicu-dev \
        libonig-dev \
        libxml2-dev \
        libzip-dev \
        mariadb-client \
        python3 \
        python3-pip \
        python3-venv \
        unzip \
    && docker-php-ext-install -j"$(nproc)" bcmath curl dom intl mbstring opcache pcntl pdo_mysql simplexml zip \
    && rm -rf /var/lib/apt/lists/*

COPY --from=composer:2 /usr/bin/composer /usr/local/bin/composer

WORKDIR /var/www/html

COPY backend/composer.json backend/composer.lock ./
RUN composer install \
    --no-dev \
    --no-interaction \
    --no-progress \
    --prefer-dist \
    --optimize-autoloader \
    --no-scripts

COPY backend/ ./
COPY --from=assets /build/public/build ./public/build
COPY omr/ /var/www/omr/
COPY omr/requirements.txt /tmp/omr-requirements.txt
COPY infra/docker/app/entrypoint.sh /usr/local/bin/gabarito360-entrypoint
COPY infra/docker/app/php.ini /usr/local/etc/php/conf.d/gabarito360.ini
COPY infra/docker/app/opcache.ini /usr/local/etc/php/conf.d/opcache.ini

RUN python3 -m venv /opt/omr \
    && /opt/omr/bin/pip install --no-cache-dir -r /tmp/omr-requirements.txt \
    && composer run-script post-autoload-dump \
    && chmod +x /usr/local/bin/gabarito360-entrypoint \
    && mkdir -p \
        storage/app/private \
        storage/framework/cache/data \
        storage/framework/sessions \
        storage/framework/views \
        storage/logs \
        bootstrap/cache \
    && chown -R www-data:www-data storage bootstrap/cache

EXPOSE 9000

ENTRYPOINT ["gabarito360-entrypoint"]
CMD ["php-fpm"]
