#!/bin/sh
# Startup script for Railway deployment
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

echo "Starting PHP server on 0.0.0.0:$PORT"
exec php -S "0.0.0.0:$PORT" -t public/
