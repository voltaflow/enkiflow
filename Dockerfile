# Stage 1: build frontend assets using Node 22
FROM node:22-alpine AS node-build
WORKDIR /app
COPY package.json package-lock.json ./
RUN npm ci
COPY resources ./resources
COPY vite.config.ts tsconfig.json ./
RUN npm run build

# Stage 2: install PHP dependencies
# Use an official Composer image
FROM composer:2.8.9 AS vendor
# Alternative approaches (commented out):
# Option 1: Use a community image with PHP 8.3 and Composer:
#FROM ghcr.io/devgine/composer-php:v2-php8.3-alpine AS vendor
# Option 2: Install Composer manually on top of PHP:
#FROM php:8.3-cli-alpine AS vendor
#COPY --from=composer:2.8.9 /usr/bin/composer /usr/local/bin/composer

WORKDIR /app
COPY composer.json composer.lock ./
RUN composer install --no-dev --optimize-autoloader

# Stage 3: production runtime with Octane
FROM php:8.3-cli-alpine AS runtime
# Install PHP extensions and Swoole
RUN apk add --no-cache --virtual .build-deps $PHPIZE_DEPS icu-dev zlib-dev libzip-dev libpng-dev libjpeg-turbo-dev freetype-dev oniguruma-dev postgresql-dev git \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) pdo_mysql pdo_pgsql bcmath intl gd opcache \
    && pecl install swoole \
    && docker-php-ext-enable swoole \
    && apk del .build-deps

ENV APP_ENV=production \ 
    APP_DEBUG=false \ 
    OCTANE_SERVER=swoole

# Create application directory and user
WORKDIR /var/www/html
RUN addgroup -S laravel && adduser -S laravel -G laravel

# Copy dependencies and code
COPY --from=vendor /app/vendor ./vendor
COPY --from=node-build /app/public ./public
COPY app app
COPY bootstrap bootstrap
COPY config config
COPY database database
COPY public/index.php public/index.php
COPY storage storage
COPY resources/views resources/views
COPY routes routes
COPY artisan .
COPY composer.json composer.lock ./

# Optimize and set permissions
RUN php artisan storage:link
RUN php artisan config:cache
RUN php artisan route:cache
RUN php artisan view:cache
RUN chown -R laravel:laravel storage bootstrap/cache

USER laravel
EXPOSE 8000
CMD ["php","artisan","octane:start","--server=swoole","--host=0.0.0.0","--port=8000"]
