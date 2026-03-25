#!/bin/sh
set -eu

cd /var/www/symfony

mkdir -p var/cache var/log
chown -R www-data:www-data var/cache var/log

if [ ! -f vendor/autoload_runtime.php ]; then
    composer install --no-interaction --prefer-dist --optimize-autoloader
fi

exec "$@"