#!/bin/bash
# Install PHP
apt-get update
apt-get install -y php php-cli php-mbstring php-xml php-curl

# Install Composer
curl -sS https://getcomposer.org/installer | php
mv composer.phar /usr/local/bin/composer

# Install dependencies
composer install --no-dev --optimize-autoloader

echo "Build complete"