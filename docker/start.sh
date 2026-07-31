#!/bin/sh
set -e

php artisan config:clear
php artisan migrate --force
php artisan storage:link || true

if [ "$RUN_SEED" = "true" ]; then
  php artisan db:seed --force
fi

php artisan serve --host=0.0.0.0 --port=${PORT:-10000}