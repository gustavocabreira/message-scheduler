FROM php:8.4-cli

LABEL maintainer="Message Scheduler"

# Install system dependencies
RUN apt-get update && apt-get install -y \
    git \
    curl \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    libpq-dev \
    libzip-dev \
    libbrotli-dev \
    zip \
    unzip \
    supervisor \
    && docker-php-ext-install pdo pdo_pgsql mbstring exif pcntl bcmath zip \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

# Install Redis extension
RUN pecl install redis && docker-php-ext-enable redis

# Install Swoole extension (brotli enabled, other optional features disabled)
RUN pecl install swoole && docker-php-ext-enable swoole

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Set working directory
WORKDIR /var/www

# Copy only composer files first (layer cache optimization)
COPY composer.json composer.lock ./

# Install PHP dependencies
RUN composer install --optimize-autoloader --no-dev --no-interaction --no-scripts

# Copy application files
COPY . .

# Run post-install scripts after full copy
RUN composer run-script post-autoload-dump --no-interaction 2>/dev/null || true

# Set permissions
RUN chown -R www-data:www-data /var/www/storage /var/www/bootstrap/cache \
    && chmod -R 755 /var/www/storage /var/www/bootstrap/cache

EXPOSE 8000

CMD ["/usr/bin/supervisord", "-c", "/var/www/docker/octane/supervisord.conf"]