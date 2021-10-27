FROM composer:2.0.8 as composer
COPY composer.json composer.json
COPY composer.lock composer.lock
RUN composer install --no-interaction --ignore-platform-reqs \
  && composer dumpautoload -o

FROM php:8.0-fpm-alpine

RUN apk --no-cache add postgresql-dev fcgi icu-dev ncurses autoconf $PHPIZE_DEPS \
  && docker-php-ext-install pdo pdo_pgsql opcache intl \
  && docker-php-ext-enable sodium \
  && mv "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini"

# Remove once base image is updated to include the latest version of curl
RUN apk upgrade curl

COPY docker/memory_limit.ini /usr/local/etc/php/conf.d/memory_limit.ini
COPY docker/opcache.ini /usr/local/etc/php/conf.d/opcache.ini
COPY docker/www.conf /usr/local/etc/php-fpm.d/www.conf

WORKDIR /var/www/
COPY public public
COPY config config
COPY --from=composer /app/vendor vendor
COPY module module
COPY tests tests
COPY MembraneDoctrineMigrations MembraneDoctrineMigrations
COPY phpstan.neon phpstan.neon
COPY phpstan.all-errors.neon phpstan.all-errors.neon
COPY phpstan-baseline.neon phpstan-baseline.neon

ENV PHP_FPM_MAX_CHILDREN "8"
ENV PHP_FPM_MEMORY_LIMIT "256M"
ENV PHP_FPM_MAX_START_CHILDREN "4"
RUN chown -R www-data /var/www/

USER "www-data"
