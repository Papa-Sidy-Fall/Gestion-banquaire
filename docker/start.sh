#!/bin/sh

# Exit on error
set -e

echo "🚀 Starting Laravel application..."

# Create .env file if it doesn't exist
if [ ! -f .env ]; then
    echo "📄 Creating .env file from .env.example..."
    cp .env.example .env || echo "Warning: .env.example not found, creating basic .env"

    # Set production environment variables
    echo "APP_ENV=production" >> .env
    echo "APP_DEBUG=false" >> .env
    echo "LOG_LEVEL=error" >> .env
    echo "CACHE_STORE=database" >> .env
    echo "SESSION_DRIVER=database" >> .env
    echo "QUEUE_CONNECTION=database" >> .env
fi

# Wait for database to be ready
echo "⏳ Waiting for database..."
echo "Environment variables:"
echo "DATABASE_URL: ${DATABASE_URL:0:50}..."
echo "DB_HOST: $DB_HOST"
echo "DB_PORT: $DB_PORT"
echo "DB_USERNAME: $DB_USERNAME"

# Try to connect for max 120 seconds (longer timeout)
i=1
while [ $i -le 60 ]; do
    if [ -n "$DATABASE_URL" ]; then
        # Extract database connection details from DATABASE_URL
        # Format: postgresql://username:password@host/database (no port in Render URLs)
        DB_USERNAME=$(echo $DATABASE_URL | sed -n 's|.*://\([^:]*\):.*|\1|p')
        DB_PASSWORD=$(echo $DATABASE_URL | sed -n 's|.*:\([^@]*\)@.*|\1|p')
        DB_HOST=$(echo $DATABASE_URL | sed -n 's|.*@\([^/]*\)/.*|\1|p')
        DB_PORT="5432"  # Always use 5432 for PostgreSQL

        # Debug: Show what we extracted
        echo "Raw URL parsing:"
        echo "  URL: $DATABASE_URL"
        echo "  Extracted - User: '$DB_USERNAME', Password: '${DB_PASSWORD:0:5}...', Host: '$DB_HOST', Port: '$DB_PORT'"

        echo "Parsed values - User: $DB_USERNAME, Host: $DB_HOST, Port: $DB_PORT"

        # Try to connect with extracted credentials
        if PGPASSWORD=$DB_PASSWORD psql -h $DB_HOST -p $DB_PORT -U $DB_USERNAME -d laravel -c "SELECT 1;" >/dev/null 2>&1; then
            echo "✅ Database is ready!"
            break
        fi
    fi

    echo "Database not ready, attempt $i/60, waiting..."
    sleep 2
    i=$((i + 1))

    if [ $i -gt 60 ]; then
        echo "❌ Database connection timeout after 120 seconds"
        echo "Final DATABASE_URL: $DATABASE_URL"
        echo "Parsed - User: $DB_USERNAME, Host: $DB_HOST, Port: $DB_PORT"
        exit 1
    fi
done

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