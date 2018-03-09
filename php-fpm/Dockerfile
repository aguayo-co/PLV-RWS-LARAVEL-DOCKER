FROM php:7.2-fpm-alpine
RUN apk add --no-cache $PHPIZE_DEPS \
    && pecl install xdebug \
    && docker-php-ext-install opcache pdo_mysql \
    && docker-php-ext-enable xdebug \
    && apk del $PHPIZE_DEPS
COPY php-fpm.d/*.conf /usr/local/etc/php-fpm.d/
COPY conf.d/*.ini /usr/local/etc/php/conf.d

WORKDIR /srv/caddy/html
