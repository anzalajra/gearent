#!/bin/bash
set -e

echo "=== Zewalo Production Entrypoint ==="

# Wait for database to be ready
echo "Waiting for database..."
until pg_isready -h ${DB_HOST:-db} -p ${DB_PORT:-5432} -U ${DB_USERNAME:-postgres} > /dev/null 2>&1; do
    sleep 2
done
echo "Database is ready!"

# Run migrations
echo "Running migrations..."
php artisan migrate --force

# Create storage/installed marker (required for app to work, not enter setup mode)
touch storage/installed

# Create storage link if not exists
if [ ! -L public/storage ]; then
    php artisan storage:link 2>/dev/null || true
fi

# Optimize for production
echo "Optimizing application..."
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache
php artisan icons:cache 2>/dev/null || true

echo "=== Application is ready! ==="

exec "$@"
