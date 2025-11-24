FROM php:8.2-cli

# Install system dependencies
RUN apt-get update && apt-get install -y \
    git \
    curl \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    zip \
    unzip \
    openssl \
    ca-certificates \
    && rm -rf /var/lib/apt/lists/*

# Install PHP extensions
RUN docker-php-ext-install pdo_mysql mbstring exif pcntl bcmath gd

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Set working directory
WORKDIR /var/www

# Copy composer files first
COPY composer.json composer.lock ./

# Install dependencies without dev packages
RUN composer install --no-dev --optimize-autoloader --no-scripts --no-interaction --ignore-platform-req=ext-* || \
    composer install --no-dev --optimize-autoloader --no-scripts --no-interaction -vvv

# Copy project files
COPY . .

# Make start script executable
RUN chmod +x start.sh

# Create required directories and set permissions
RUN mkdir -p var/cache var/log public/uploads config/serialization \
    && chmod -R 777 var/ \
    && chown -R www-data:www-data /var/www

# Set production environment
ENV APP_ENV=prod
ENV APP_DEBUG=0
# DATABASE_URL will be provided by Railway environment variables
ENV TRUSTED_PROXIES=127.0.0.1,REMOTE_ADDR
ENV TRUSTED_HOSTS=^.*$

# Generate JWT keys if they don't exist
RUN mkdir -p config/jwt \
    && if [ ! -f config/jwt/private.pem ]; then \
    openssl genpkey -algorithm RSA -out config/jwt/private.pem -pkeyopt rsa_keygen_bits:4096; \
    openssl rsa -pubout -in config/jwt/private.pem -out config/jwt/public.pem; \
    chmod 644 config/jwt/private.pem config/jwt/public.pem; \
    fi

# Run post-install scripts, migrations, and warm up cache
RUN composer run-script post-install-cmd --no-interaction || true \
    && rm -rf var/cache/* var/log/* \
    && php bin/console doctrine:database:create --if-not-exists --env=prod --no-debug || true \
    && php bin/console doctrine:migrations:migrate --no-interaction --env=prod --no-debug || true \
    && rm -rf var/cache/prod \
    && php bin/console cache:clear --env=prod --no-debug --no-warmup || true \
    && php bin/console cache:warmup --env=prod --no-debug || true

# Expose port
EXPOSE 8080

# Start command - use shell form to allow PORT variable substitution
CMD ["sh", "start.sh"]