#!/bin/bash
set -e

echo "=== Zewalo Production Entrypoint ==="

# Sync public files from build to volume (ensures fresh assets on every deploy)
echo "Syncing public files..."
rsync -a --delete /var/www/public-build/ /var/www/public/

# Wait for database to be ready
echo "Waiting for database..."
until pg_isready -h ${DB_HOST:-db} -p ${DB_PORT:-5432} -U ${DB_USERNAME:-postgres} > /dev/null 2>&1; do
    sleep 2
done
echo "Database is ready!"

# Run migrations (default path: tenants & domains tables)
echo "Running default migrations..."
php artisan migrate --force

# Run central migrations (users, permissions, subscription_plans, etc.)
echo "Running central migrations..."
php artisan migrate --path=database/migrations/central --database=central --force

# Run tenant migrations (all existing tenant databases)
echo "Running tenant migrations..."
php artisan tenants:migrate --force 2>/dev/null || true

# Seed central database on first deploy (creates admin user and roles)
if [ ! -f storage/seeded ]; then
    echo "First deploy detected, seeding central database..."
    php artisan db:seed --class=CentralSeeder --force
    touch storage/seeded
fi

# Create storage/installed marker (required for app to work, not enter setup mode)
touch storage/installed

# Create storage link if not exists
if [ ! -L public/storage ]; then
    php artisan storage:link || true
fi

# Publish Filament assets (must run at runtime since build has no .env)
echo "Publishing Filament assets..."
php artisan filament:assets

# Optimize for production
echo "Optimizing application..."
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache
php artisan icons:cache || true

echo "=== Application is ready, starting services ==="

exec "$@"
