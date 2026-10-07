FROM php:8.2-apache

RUN apt-get update && apt-get install -y libpq-dev \
    && docker-php-ext-install pdo pdo_pgsql

COPY . /var/www/html/

RUN chown -R www-data:www-data /var/www/html
RUN touch /var/www/html/tokens.json && chmod 666 /var/www/html/tokens.json

EXPOSE 80
