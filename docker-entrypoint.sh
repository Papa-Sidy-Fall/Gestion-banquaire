#!/bin/sh

# Clear and cache configuration, routes, and views
php artisan config:clear
php artisan cache:clear
php artisan route:clear
php artisan view:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Run migrations
php artisan migrate --force

echo "Starting Laravel application..."
exec "$@"
