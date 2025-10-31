#!/bin/sh

# Diagnostiquer les modules PHP chargés
echo "PHP Modules Loaded:"
php -m

# Attendre que la base de données soit prête
echo "Waiting for database to be ready..."
while ! pg_isready -h $DB_HOST -p $DB_PORT -U $DB_USERNAME; do
  echo "Database is unavailable - sleeping"
  sleep 1
done

echo "Database is up - executing migrations"

# Clear and cache configuration, routes, and views
php artisan config:clear
php artisan cache:clear
php artisan route:clear
php artisan view:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache

php artisan migrate --force

echo "Starting Laravel application..."
exec "$@"
