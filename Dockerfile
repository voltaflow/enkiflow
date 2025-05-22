
# Multi-stage Dockerfile for Laravel
# Production-ready setup for Kubernetes deployment

# Build Arguments
ARG APP_ENV=production

# Stage 1: Composer dependencies
FROM php:8.3-cli-alpine AS composer

WORKDIR /app

# Install required PHP extensions for Composer dependencies
RUN apk add --no-cache --virtual .build-deps $PHPIZE_DEPS git unzip \
    && docker-php-ext-install bcmath \
    && apk add --no-cache git unzip

# Install Composer
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

# Copy only composer files first for better layer caching
COPY composer.json composer.lock ./

# Install production dependencies only
RUN composer install \
    --no-dev \
    --optimize-autoloader \
    --apcu-autoloader \
    --no-scripts \
    --no-interaction

# Now copy the entire codebase for further processing
COPY . .

# Run post-install scripts now that we have the full codebase
RUN composer dump-autoload

# Stage 2: Node build (if you have frontend assets)
FROM node:lts-alpine AS node-build

WORKDIR /app

# Copy package files for better layer caching
COPY package*.json ./
COPY vite.config.ts tsconfig.json ./

# Install dependencies
RUN npm ci

# Copy application code
COPY resources/ ./resources/
COPY public/ ./public/

# Build frontend assets
RUN npm run build

# Stage 3: Production image
FROM php:8.3-cli-alpine AS runtime

# Production environment variables
ENV APP_ENV=production \
    APP_DEBUG=false \
    LOG_LEVEL=info

# The www-data user already exists in the base image
# Just ensure we have the directories we need with proper permissions

# Install required runtime dependencies first
RUN apk add --no-cache \
    curl \
    libpq \
    libpng \
    libjpeg-turbo \
    libzip \
    icu-libs \
    libpq \
    oniguruma \
    freetype \
    harfbuzz \
    libjpeg

# Install build dependencies
RUN apk add --no-cache --virtual .build-deps $PHPIZE_DEPS \
    icu-dev \
    zlib-dev \
    libpng-dev \
    libjpeg-turbo-dev \
    freetype-dev \
    postgresql-dev

# Configure and install PHP extensions
RUN docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) \
        bcmath \
        intl \
        pdo_mysql \
        pdo_pgsql \
        gd \
        opcache \
        pcntl

# Install PECL extensions
RUN pecl install redis \
    && docker-php-ext-enable redis opcache

# Enable JIT compilation for PHP 8.3
RUN echo "opcache.jit=1255" >> /usr/local/etc/php/conf.d/docker-php-ext-opcache.ini \
    && echo "opcache.jit_buffer_size=128M" >> /usr/local/etc/php/conf.d/docker-php-ext-opcache.ini

# Set production PHP settings
RUN echo "memory_limit=256M" > /usr/local/etc/php/conf.d/memory-limit.ini \
    && echo "max_execution_time=30" >> /usr/local/etc/php/conf.d/memory-limit.ini

# Cleanup - more cautious approach
RUN apk del .build-deps \
    && rm -rf /var/cache/apk/* \
    && rm -rf /tmp/pear*

# Prepare application directory
WORKDIR /var/www/html

# First copy application code (base layer)
COPY . /var/www/html

# Then copy Composer binary and overwrite with vendor directory from composer stage
COPY --from=composer /usr/local/bin/composer /usr/local/bin/composer
COPY --from=composer /app/vendor /var/www/html/vendor

# Create necessary directories
RUN mkdir -p /var/www/html/public/build /var/www/html/bootstrap/cache

# Finally, try to copy frontend assets from node-build stage if they exist
RUN mkdir -p /var/www/html/public/build
COPY --from=node-build /app/public/build/ /var/www/html/public/build/

# Set proper permissions
RUN chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache \
    && chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache

# Use non-root user
USER www-data

# Expose the Octane server port
EXPOSE 8000

# Add healthcheck
HEALTHCHECK --interval=10s --timeout=5s --start-period=30s --retries=3 \
    CMD curl -fsS http://localhost:8000/health || exit 1

# Use regular Laravel server instead of Octane
# Also ensure we apply migrations before starting
CMD ["php", "-S", "0.0.0.0:8000", "-t", "public/"]
# Alternative: use built-in artisan serve
# CMD ["php", "artisan", "serve", "--host=0.0.0.0", "--port=8000"]