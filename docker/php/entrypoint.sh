#!/bin/sh
set -e

if [ -f /var/www/html/composer.json ] && [ ! -d /var/www/html/vendor ]; then
    echo "Installing Composer dependencies..."
    composer install --no-interaction --no-dev --working-dir=/var/www/html
fi

exec "$@"
