FROM php:8.2-apache

RUN docker-php-ext-install pdo pdo_mysql

# Apache lang nghe cong 8080 de co the chay bang user thuong (non-root)
RUN sed -ri 's/^Listen 80$/Listen 8080/' /etc/apache2/ports.conf \
 && sed -ri 's/<VirtualHost \*:80>/<VirtualHost *:8080>/' /etc/apache2/sites-available/000-default.conf

WORKDIR /var/www/html

COPY public/ /var/www/html/
COPY config/ /var/www/config/

EXPOSE 8080

USER www-data
