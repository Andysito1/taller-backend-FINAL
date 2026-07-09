# syntax=docker/dockerfile:1

############################
# 1) Build frontend (Vite)
############################
FROM node:20-alpine AS frontend
WORKDIR /app

# Install JS deps first (better layer caching)
COPY package.json package-lock.json* ./
RUN npm ci

# Copy only what we need for build
COPY vite.config.js ./
COPY resources ./resources

# Build assets
RUN npm run build

############################
# 2) Install PHP deps (composer)
############################
FROM php:8.2-fpm-alpine AS php-deps

# System deps for common Laravel packages
RUN apk add --no-cache \
    bash \
    git \
    unzip \
    oniguruma-dev \
    libzip-dev \
    && docker-php-ext-install pdo pdo_mysql mbstring zip

WORKDIR /var/www/html

# Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Copy composer files and install deps
COPY composer.json composer.lock ./
RUN composer install --no-dev --no-interaction --prefer-dist --optimize-autoloader

############################
# 3) Final runtime image
############################
FROM php:8.2-fpm-alpine

RUN apk add --no-cache \
    bash \
    oniguruma-dev \
    libzip-dev \
    && docker-php-ext-install pdo pdo_mysql mbstring zip

WORKDIR /var/www/html

# Copy PHP vendor + app code
COPY --from=php-deps /var/www/html /var/www/html

# Copy the rest of the Laravel app
COPY . .

# Copy built frontend assets
COPY --from=frontend /app/public /var/www/html/public

# Ensure writable dirs
RUN chown -R www-data:www-data storage bootstrap/cache || true

EXPOSE 9000

# Opcional: comandos de optimización (ojo con depender de DB/env)
# RUN php artisan config:cache route:cache view:cache || true

CMD ["php-fpm","-y","/usr/local/etc/php-fpm.conf"]

