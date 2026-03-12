#!/bin/sh
set -e

if [ "$1" = "php-fpm" ] || [ "$1" = "php" ]; then
    echo "Clearing cache..."
    php bin/console cache:clear --no-interaction

    echo "Running migrations..."
    php bin/console doctrine:migrations:migrate --no-interaction --allow-no-migration || echo "Migrations skipped"
fi

exec "$@"
