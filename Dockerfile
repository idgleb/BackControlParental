# -------- Build stage (Composer dependencies) --------
FROM composer:2 AS build

# Set working directory
WORKDIR /var/www

# Copy Composer manifests first (leverages cache for dependencies)
COPY composer.json composer.lock ./

# Copy the rest of the application so that artisan is present for Composer scripts
COPY . ./

# Ensure Laravel required directories exist then install PHP dependencies
RUN mkdir -p storage/framework/cache storage/framework/cache/data storage/framework/sessions storage/framework/views storage/framework/testing storage/logs bootstrap/cache \
    && composer install --optimize-autoloader --no-interaction --no-progress

# Copy full application source
COPY . .

# -------- Runtime stage --------
FROM php:8.3-fpm-alpine

# Install system dependencies & PHP extensions required by Laravel
RUN apk add --no-cache --virtual .build-deps $PHPIZE_DEPS \
    && apk add --no-cache icu-dev oniguruma-dev libzip-dev git \
    && docker-php-ext-install pdo_mysql bcmath intl zip \
    && apk del -f .build-deps

# Configure working directory inside the container
WORKDIR /var/www

# Copy the compiled application from the build stage
COPY --from=build /var/www /var/www

# Expose port 9000 and start php-fpm
EXPOSE 9000
CMD ["php-fpm"] 