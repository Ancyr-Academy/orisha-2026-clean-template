FROM dunglas/frankenphp:1-php8.5

RUN install-php-extensions pdo_pgsql opcache zip

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Plain HTTP on port 80 (no automatic HTTPS for local development)
ENV SERVER_NAME=:80

WORKDIR /app

COPY composer.json composer.lock ./
RUN composer install --no-interaction --prefer-dist --no-scripts --no-autoloader

COPY . .
RUN composer dump-autoload --optimize

COPY docker/entrypoint.sh /usr/local/bin/acme-entrypoint
RUN chmod +x /usr/local/bin/acme-entrypoint bin/console

ENTRYPOINT ["acme-entrypoint"]
CMD ["--config", "/etc/frankenphp/Caddyfile", "--adapter", "caddyfile"]
