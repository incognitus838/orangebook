FROM php:8.4-cli-bookworm

RUN apt-get update && apt-get install -y --no-install-recommends \
    git unzip libzip-dev libpng-dev libonig-dev libxml2-dev libicu-dev \
    && docker-php-ext-install pdo_mysql mbstring zip gd intl bcmath pcntl \
    && rm -rf /var/lib/apt/lists/*

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /app
COPY . .

RUN composer install --no-dev --optimize-autoloader --no-interaction \
    && mkdir -p storage/framework/{cache,sessions,views} storage/logs bootstrap/cache \
    && chmod -R ug+rwx storage bootstrap/cache

ENV APP_ENV=production
ENV LOG_CHANNEL=stderr

EXPOSE 8080
CMD php artisan migrate --force && php artisan serve --host 0.0.0.0 --port ${PORT:-8080}
