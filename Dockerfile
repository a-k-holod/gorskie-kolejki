FROM php:8.2-fpm

RUN apt-get update && apt-get install -y \
    git \
    unzip \
    libicu-dev \
    && docker-php-ext-configure intl \
    && docker-php-ext-install intl pdo pdo_mysql

# Instalacja Composer
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

WORKDIR /var/www

COPY . /var/www
RUN mkdir -p storage/logs && chmod -R 777 storage

RUN composer install --no-interaction --prefer-dist --optimize-autoloader
