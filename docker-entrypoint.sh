#!/bin/sh

# Clear and cache configuration, routes, and views
echo "Clearing caches..."
php artisan config:clear
php artisan cache:clear
php artisan route:clear
php artisan view:clear

echo "Caching configuration..."
php artisan config:cache

echo "Caching routes..."
php artisan route:cache

echo "Caching views..."
php artisan view:cache

echo "Cache operations completed successfully"

# Run migrations
php artisan migrate --force

echo "Starting Laravel application..."
exec "$@"
