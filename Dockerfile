# Use official PHP 8.2 FPM Alpine image for lightweight production environment
FROM php:8.2-fpm-alpine

# Install system dependencies
RUN apk add --no-cache \
    git \
    curl \
    libpng-dev \
    libjpeg-turbo-dev \
    libwebp-dev \
    libxpm-dev \
    freetype-dev \
    oniguruma-dev \
    libxml2-dev \
    zip \
    unzip \
    bash \
    postgresql-dev \
    libpq

# Configure and install PHP extensions
RUN docker-php-ext-configure gd --with-freetype --with-jpeg --with-webp
RUN docker-php-ext-install \
    pdo_pgsql \
    pgsql \
    mbstring \
    exif \
    pcntl \
    gd \
    bcmath \
    xml \
    soap

# Install Redis PHP extension
RUN pecl install redis && docker-php-ext-enable redis

# Install Composer from official Composer image
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Set working directory
WORKDIR /var/www/html

# Copy application source code
COPY . .

# Install production Composer dependencies
RUN composer install --optimize-autoloader --no-dev --no-interaction

# Set proper permissions for Laravel required directories
RUN chown -R www-data:www-data storage bootstrap/cache
RUN chmod -R 775 storage bootstrap/cache

# Expose PHP-FPM port
EXPOSE 9000
