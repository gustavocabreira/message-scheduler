FROM php:8.4-fpm

ARG UID=1000
ARG GID=1000

# System dependencies
RUN apt-get update && apt-get install -y \
    supervisor curl unzip libpq-dev libzip-dev \
    && docker-php-ext-install pdo pdo_pgsql pcntl zip \
    && pecl install redis \
    && docker-php-ext-enable redis \
    && rm -rf /var/lib/apt/lists/*

# Remap www-data to match host UID/GID so files created by PHP match the host user
RUN groupmod -g ${GID} www-data 2>/dev/null || true \
    && usermod -u ${UID} www-data 2>/dev/null || true

# Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

COPY . .

# Supervisor config
COPY docker/supervisor/supervisord.conf /etc/supervisor/conf.d/supervisord.conf
COPY docker/entrypoint.sh /usr/local/bin/entrypoint.sh

# php-fpm pid in writable location for non-root container user
RUN printf '[global]\npid = /tmp/php-fpm.pid\n' > /usr/local/etc/php-fpm.d/zz-pid.conf

# vendor dir owned by host UID so the named volume is initialized with correct ownership
RUN mkdir -p /var/www/html/vendor && chown ${UID}:${GID} /var/www/html/vendor

RUN mkdir -p /var/log/supervisor \
    && chmod 777 /var/log/supervisor \
    && chmod +x /usr/local/bin/entrypoint.sh

EXPOSE 9000

ENTRYPOINT ["/usr/local/bin/entrypoint.sh"]
CMD ["/usr/bin/supervisord", "-c", "/etc/supervisor/conf.d/supervisord.conf"]
