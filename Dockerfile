FROM php:8.3-fpm

RUN apt-get update && apt-get install -y \
    git curl zip unzip libxml2-dev libzip-dev libpng-dev \
    && docker-php-ext-install pdo_mysql xml zip

COPY --from=composer:2.7 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www

COPY . .

RUN composer install --no-interaction --prefer-dist

RUN chown -R www-data:www-data /var/www/storage /var/www/bootstrap/cache

EXPOSE 8000

CMD php artisan serve --host=0.0.0.0 --port=8000
