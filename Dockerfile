# syntax=docker/dockerfile:1

FROM composer:2 AS vendor
WORKDIR /app

COPY composer.json composer.lock ./
RUN composer install \
    --no-dev \
    --no-interaction \
    --no-progress \
    --prefer-dist \
    --optimize-autoloader \
    --no-scripts

COPY app app
COPY artisan artisan
COPY bootstrap bootstrap
COPY config config
COPY database database
COPY routes routes
COPY resources resources

RUN composer dump-autoload --no-dev --optimize \
    && php artisan package:discover --ansi

FROM node:20-alpine AS assets
WORKDIR /app

COPY package.json package-lock.json ./
RUN npm ci

COPY resources resources
COPY public public
COPY vite.config.js postcss.config.js tailwind.config.js ./
RUN npm run build

FROM php:8.3-apache-bookworm AS app
WORKDIR /var/www/html

RUN apt-get update \
    && apt-get install -y --no-install-recommends \
        curl \
        libfreetype6-dev \
        libjpeg62-turbo-dev \
        libpng-dev \
        libzip-dev \
        unzip \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j"$(nproc)" \
        bcmath \
        exif \
        gd \
        opcache \
        pcntl \
        pdo_mysql \
        zip \
    && a2enmod headers rewrite \
    && rm -rf /var/lib/apt/lists/*

COPY docker/apache/000-default.conf /etc/apache2/sites-available/000-default.conf
COPY docker/php/production.ini /usr/local/etc/php/conf.d/production.ini
COPY docker/entrypoint.sh /usr/local/bin/intrams-entrypoint

COPY --chown=www-data:www-data . .
COPY --from=vendor --chown=www-data:www-data /app/vendor vendor
COPY --from=vendor --chown=www-data:www-data /app/bootstrap/cache bootstrap/cache
COPY --from=assets --chown=www-data:www-data /app/public/build public/build

RUN chmod +x /usr/local/bin/intrams-entrypoint \
    && mkdir -p \
        storage/app/public \
        storage/framework/cache/data \
        storage/framework/sessions \
        storage/framework/views \
        storage/logs \
        bootstrap/cache \
    && chown -R www-data:www-data storage bootstrap/cache

EXPOSE 80

HEALTHCHECK --interval=30s --timeout=5s --start-period=30s --retries=3 \
    CMD curl -fsS http://127.0.0.1/up || exit 1

ENTRYPOINT ["intrams-entrypoint"]
CMD ["apache2-foreground"]
