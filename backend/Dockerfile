FROM php:8.2-cli

RUN apt-get update \
    && apt-get install -y --no-install-recommends libpq-dev unzip git curl \
    && docker-php-ext-install pdo_pgsql \
    && curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer \
    && rm -rf /var/lib/apt/lists/*

WORKDIR /var/www/html
COPY . .

RUN composer install --no-dev --prefer-dist --optimize-autoloader --no-interaction \
    && chown -R www-data:www-data storage bootstrap/cache \
    && chmod -R ug+rwX storage bootstrap/cache \
    && ln -s ../storage/app/public public/storage

EXPOSE 8000
CMD ["/bin/sh", "-c", "php artisan config:cache >/dev/null 2>&1 || true; php artisan route:cache >/dev/null 2>&1 || true; php artisan serve --host 0.0.0.0 --port ${PORT:-8000}"]
