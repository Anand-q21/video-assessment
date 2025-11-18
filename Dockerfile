FROM php:8.1-cli

# Install system dependencies
RUN apt-get update && apt-get install -y \
    git \
    curl \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    zip \
    unzip \
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
RUN composer install --no-dev --optimize-autoloader --no-scripts --no-interaction

# Copy project files
COPY . .

# Create required directories and set permissions
RUN mkdir -p var/cache var/log public/uploads \
    && chmod -R 777 var/ \
    && chown -R www-data:www-data /var/www

# Run post-install scripts
RUN composer run-script post-install-cmd --no-interaction || true

# Expose port
EXPOSE 8080

# Start command
CMD ["php", "-S", "0.0.0.0:8080", "-t", "public/"]