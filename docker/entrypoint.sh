#!/bin/sh
set -e

if [ -f composer.json ] && [ ! -f vendor/autoload.php ]; then
    echo "==> dependencies are missing, installing them"
    composer install --no-interaction --no-progress
    echo "==> dependencies installed"
fi

exec "$@"
