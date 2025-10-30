#!/bin/sh

# Exit on error
set -e

echo "🚀 Starting Laravel application..."

# Extract database connection details from DATABASE_URL
if [ -n "$DATABASE_URL" ]; then
    echo "Raw URL parsing:"
    echo "  URL: $DATABASE_URL"
    DB_CONNECTION=$(echo $DATABASE_URL | sed -n 's|^\([^:]*\):.*|\1|p')
    DB_USERNAME=$(echo $DATABASE_URL | sed -n 's|.*://\([^:]*\):.*|\1|p')
    DB_PASSWORD=$(echo $DATABASE_URL | sed -n 's|.*:\([^@]*\)@.*|\1|p')
    DB_HOST=$(echo $DATABASE_URL | sed -n 's|.*@\([^:/]*\).*|\1|p')
    DB_PORT=$(echo $DATABASE_URL | sed -n 's|.*:\([0-9]*\)/.*|\1|p')
    DB_DATABASE=$(echo $DATABASE_URL | sed -n 's|.*/\([^?]*\).*|\1|p')

    # Default port for PostgreSQL if not specified in URL
    if [ -z "$DB_PORT" ]; then
        DB_PORT="5432"
    fi

    echo "  Extracted - User: '$DB_USERNAME', Password: '${DB_PASSWORD:0:5}...', Host: '$DB_HOST', Port: '$DB_PORT', Database: '$DB_DATABASE'"
    echo "Parsed values - User: $DB_USERNAME, Host: $DB_HOST, Port: $DB_PORT, Database: $DB_DATABASE"

    # Set environment variables for Laravel
    export DB_CONNECTION=$DB_CONNECTION
    export DB_HOST=$DB_HOST
    export DB_PORT=$DB_PORT
    export DB_DATABASE=$DB_DATABASE
    export DB_USERNAME=$DB_USERNAME
    export DB_PASSWORD=$DB_PASSWORD

    echo "Database connection variables exported."
fi

# Create .env file if it doesn't exist and APP_ENV is not already set (e.g., by Render)
if [ ! -f .env ] && [ -z "$APP_ENV" ]; then
    echo "📄 Creating .env file from .env.example..."
    cp .env.example .env || echo "Warning: .env.example not found, creating basic .env"
    # Add basic production settings if not already set by environment variables
    echo "APP_ENV=production" >> .env
    echo "APP_DEBUG=false" >> .env
    echo "LOG_LEVEL=error" >> .env
    echo "CACHE_STORE=database" >> .env
    echo "SESSION_DRIVER=database" >> .env
    echo "QUEUE_CONNECTION=database" >> .env
fi

# Wait for database to be ready
echo "⏳ Waiting for database..."
# Try to connect for max 120 seconds (longer timeout)
i=1
while [ $i -le 60 ]; do
    if PGPASSWORD=$DB_PASSWORD psql -h $DB_HOST -p $DB_PORT -U $DB_USERNAME -d $DB_DATABASE -c "SELECT 1;" >/dev/null 2>&1; then
        echo "✅ Database is ready!"
        break
    else
        echo "Connection failed. Checking if database exists..."
        # Try to connect to postgres database to check if server is up
        if PGPASSWORD=$DB_PASSWORD psql -h $DB_HOST -p $DB_PORT -U $DB_USERNAME -d postgres -c "SELECT version();" >/dev/null 2>&1; then
            echo "PostgreSQL server is up, but database '$DB_DATABASE' may not exist or credentials are wrong."
        else
            echo "Cannot connect to PostgreSQL server at all."
        fi
    fi

    echo "Database not ready, attempt $i/60, waiting..."
    sleep 2
    i=$((i + 1))

    if [ $i -gt 60 ]; then
        echo "❌ Database connection timeout after 120 seconds"
        echo "Final DB_HOST: $DB_HOST, DB_PORT: $DB_PORT, DB_USERNAME: $DB_USERNAME, DB_DATABASE: $DB_DATABASE"
        exit 1
    fi
done

# Generate application key if not set
if [ -z "$APP_KEY" ] || [ "$APP_KEY" = "base64:" ]; then
    echo "🔑 Generating application key..."
    php artisan key:generate --force
fi

# Clear config cache before running migrations to ensure new DB vars are used
echo "⚡ Clearing config cache..."
php artisan config:clear

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
