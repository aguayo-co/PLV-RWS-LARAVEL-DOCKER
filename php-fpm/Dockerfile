FROM php:7.2-fpm-alpine3.7
RUN docker-php-ext-install opcache

COPY php-fpm.d/*.conf /usr/local/etc/php-fpm.d/
