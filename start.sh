#!/bin/sh
# Startup script for Render deployment
# Uses PORT environment variable with fallback to 8080

echo "Starting application..."
echo "PORT environment variable: ${PORT}"

# Set PORT with fallback
if [ -z "$PORT" ]; then
    echo "PORT not set, using default 8080"
    PORT=8080
else
    echo "Using PORT: $PORT"
fi

# Run database migrations
echo "Running database migrations..."
php bin/console doctrine:migrations:migrate --no-interaction --allow-no-migration || {
    echo "Migration failed, but continuing..."
}

# Clear and warm up cache
echo "Clearing cache..."
php bin/console cache:clear --no-warmup || true
php bin/console cache:warmup || true

echo "Starting PHP server on [::]:$PORT"
exec php -S "[::]:$PORT" -t public/

