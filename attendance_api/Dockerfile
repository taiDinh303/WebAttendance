FROM php:8.4-apache

RUN apt-get update \
    && apt-get install -y --no-install-recommends ca-certificates libicu-dev libonig-dev libzip-dev unzip \
    && docker-php-ext-install -j"$(nproc)" pdo_mysql mbstring intl zip opcache pcntl \
    && a2enmod rewrite \
    && rm -rf /var/lib/apt/lists/*

COPY --from=composer:2 /usr/bin/composer /usr/local/bin/composer
WORKDIR /var/www/html
COPY --chown=www-data:www-data . .
RUN composer install --no-dev --prefer-dist --no-interaction --no-progress --optimize-autoloader \
    && mkdir -p storage/framework/cache/data storage/framework/sessions storage/framework/views storage/logs \
    && chown -R www-data:www-data storage bootstrap/cache

COPY apache.conf /etc/apache2/sites-available/000-default.conf
COPY docker-entrypoint.sh /usr/local/bin/attendance-entrypoint
RUN printf 'expose_php=Off\nupload_max_filesize=20M\npost_max_size=24M\nmemory_limit=128M\nopcache.enable=1\nopcache.validate_timestamps=0\n' > /usr/local/etc/php/conf.d/attendance.ini

EXPOSE 10000
ENTRYPOINT ["sh", "/usr/local/bin/attendance-entrypoint"]
CMD ["apache2-foreground"]
