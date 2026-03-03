#!/bin/bash
set -e

# Wait for database to be ready
echo "Waiting for database..."
while ! pg_isready -h db -p 5432 -U ${DB_USERNAME:-postgres} > /dev/null 2>&1; do
    sleep 1
done
echo "Database is ready!"

# Run migrations
echo "Running migrations..."
php artisan migrate --force

# Clear and cache configs for production
echo "Optimizing application..."
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache
php artisan icons:cache

# Create storage link if not exists
if [ ! -L public/storage ]; then
    php artisan storage:link
fi

# Set permissions
chown -R www-data:www-data storage bootstrap/cache
chmod -R 775 storage bootstrap/cache

echo "Application is ready!"

exec "$@"
