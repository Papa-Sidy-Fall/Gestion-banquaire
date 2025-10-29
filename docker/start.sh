#!/bin/sh

# Exit on error
set -e

echo "🚀 Starting Laravel application..."

# Wait for database to be ready
echo "⏳ Waiting for database..."
if [ -n "$DATABASE_URL" ]; then
    echo "DATABASE_URL found: ${DATABASE_URL:0:30}..."

    # Extract database connection details from DATABASE_URL
    # Format: postgresql://username:password@host:port/database
    DB_HOST=$(echo $DATABASE_URL | sed -n 's|.*@\([^:]*\):.*|\1|p')
    DB_PORT=$(echo $DATABASE_URL | sed -n 's|.*:\([0-9]*\)/.*|\1|p')
    DB_USERNAME=$(echo $DATABASE_URL | sed -n 's|.*://\([^:]*\):.*|\1|p')
    DB_PASSWORD=$(echo $DATABASE_URL | sed -n 's|.*:\([^@]*\)@.*|\1|p')

    echo "Parsed connection: host=$DB_HOST, port=$DB_PORT, user=$DB_USERNAME"

    # Try to connect for max 60 seconds
    for i in {1..30}; do
        if pg_isready -h $DB_HOST -p $DB_PORT -U $DB_USERNAME -d postgres >/dev/null 2>&1; then
            echo "✅ Database is ready!"
            break
        fi
        echo "Database not ready, attempt $i/30, waiting..."
        sleep 2

        if [ $i -eq 30 ]; then
            echo "❌ Database connection timeout after 60 seconds"
            exit 1
        fi
    done
else
    echo "❌ No DATABASE_URL found"
    exit 1
fi

# Generate application key if not set
if [ -z "$APP_KEY" ] || [ "$APP_KEY" = "base64:" ]; then
    echo "🔑 Generating application key..."
    php artisan key:generate --force
fi

# Run database migrations
echo "🗄️ Running database migrations..."
php artisan migrate --force

# Seed database if needed
if [ "$APP_ENV" = "local" ]; then
    echo "🌱 Seeding database..."
    php artisan db:seed --force
fi

# Generate Swagger documentation
echo "📚 Generating API documentation..."
php artisan l5-swagger:generate

# Clear and cache config
echo "⚡ Optimizing Laravel..."
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Set proper permissions
echo "🔒 Setting permissions..."
chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache
chmod -R 755 /var/www/html/storage /var/www/html/bootstrap/cache

# Create log files if they don't exist
touch /var/log/nginx/access.log /var/log/nginx/error.log
chown www-data:www-data /var/log/nginx/*.log

echo "🎯 Starting services..."

# Start supervisor (which manages nginx and php-fpm)
exec /usr/bin/supervisord -c /etc/supervisord.conf