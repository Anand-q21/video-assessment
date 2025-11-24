#!/bin/sh
# Startup script for Railway deployment
# Uses PORT environment variable with fallback to 8080

PORT=${PORT:-8080}
php -S 0.0.0.0:$PORT -t public/
