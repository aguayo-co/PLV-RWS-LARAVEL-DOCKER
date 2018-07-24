FROM php:7.2-fpm-alpine
RUN apk add --no-cache $PHPIZE_DEPS libxml2-dev libjpeg-turbo-dev libpng-dev freetype-dev \
    && pecl install xdebug \
    && docker-php-ext-configure gd --with-freetype-dir=/usr/include/ --with-jpeg-dir=/usr/include/ \
    && docker-php-ext-install soap opcache pdo_mysql gd \
    && docker-php-ext-enable xdebug \
    && apk del $PHPIZE_DEPS
COPY php-fpm.d/*.conf /usr/local/etc/php-fpm.d/
COPY conf.d/*.ini /usr/local/etc/php/conf.d

COPY --from=composer /usr/bin/composer /usr/bin/composer

WORKDIR /srv/caddy/html
