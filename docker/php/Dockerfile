FROM php:8.3-fpm-alpine
RUN apk add --no-cache \
    git curl libpng-dev libxml2-dev zip unzip \
    oniguruma-dev nodejs npm mysql-client
RUN docker-php-ext-install pdo pdo_mysql mbstring bcmath xml
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer
WORKDIR /var/www/html
RUN addgroup -g 1000 laravel && adduser -G laravel -g laravel -s /bin/sh -D laravel
USER laravel
