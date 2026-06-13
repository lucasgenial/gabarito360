#!/bin/sh
set -eu

mkdir -p \
    storage/app/private \
    storage/framework/cache/data \
    storage/framework/sessions \
    storage/framework/views \
    storage/logs \
    bootstrap/cache

chown -R www-data:www-data storage bootstrap/cache

case "${1:-}" in
    php-fpm)
        exec "$@"
        ;;
    *)
        exec gosu www-data "$@"
        ;;
esac
