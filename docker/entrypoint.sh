#!/bin/sh
set -e

# The project directory is bind-mounted in development: install dependencies if they are missing.
if [ ! -f vendor/autoload.php ]; then
    composer install --no-interaction --prefer-dist
fi

php bin/console migrations:migrate --no-interaction --allow-no-migration

exec docker-php-entrypoint "$@"
