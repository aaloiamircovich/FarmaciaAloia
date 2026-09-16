FROM php:8.3-cli

RUN apt-get update \
    && apt-get install -y --no-install-recommends \
        libcurl4-openssl-dev \
        libonig-dev \
        libzip-dev \
        unzip \
    && docker-php-ext-install mysqli mbstring curl zip \
    && rm -rf /var/lib/apt/lists/*

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /app

COPY composer.json composer.lock* ./
RUN composer install --no-dev --no-interaction --prefer-dist --optimize-autoloader

COPY . .

RUN mkdir -p /app/uploads/recetas \
    && chmod -R 775 /app/uploads

EXPOSE 8080

CMD ["sh", "-c", "php scripts/init_db.php && exec php -S 0.0.0.0:${PORT:-8080} router.php"]
