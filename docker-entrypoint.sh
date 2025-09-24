#!/bin/bash

# Wait for MySQL to be ready
echo "Waiting for MySQL to be ready..."
until mysql -h mysql -u partzona_user -p"password" -e "SELECT 1" >/dev/null 2>&1; do
  echo "Waiting for MySQL..."
  sleep 2
done

echo "MySQL is ready!"

# Copy environment file
cp .env.docker .env

# Generate application key if not set
php artisan key:generate --force

# Clear cache
php artisan config:clear
php artisan cache:clear
php artisan route:clear
php artisan view:clear

# Run migrations
php artisan migrate --force

# Seed database (if needed)
# php artisan db:seed --force

# Set proper permissions
chown -R www-data:www-data storage bootstrap/cache
chmod -R 775 storage bootstrap/cache

echo "Laravel application setup completed!"

# Start php-fpm
exec php-fpm