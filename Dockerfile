FROM composer:2 AS dependencies

WORKDIR /app
COPY composer.json ./
RUN composer install --no-dev --prefer-dist --no-interaction --no-progress --optimize-autoloader

FROM php:8.3-apache

COPY docker/apache-bibliocusiana.conf /etc/apache2/conf-available/bibliocusiana.conf

RUN cp "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini" \
    && docker-php-ext-install mysqli \
    && a2enmod headers \
    && a2enconf bibliocusiana

COPY --from=dependencies /app/vendor/ /var/www/html/vendor/
COPY . /var/www/html/

EXPOSE 80
