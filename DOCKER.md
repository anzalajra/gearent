# Zewalo - Docker Deployment Guide

## Prerequisites

- Docker Engine 20.10+
- Docker Compose V2+
- (Optional) Make utility

## Quick Start - Development

```bash
# Clone repository
git clone <repository-url>
cd zewalo

# Copy environment file
cp .env.docker .env

# Generate application key
php artisan key:generate

# Start development environment
docker compose -f docker-compose.dev.yml up -d

# Run migrations
docker compose -f docker-compose.dev.yml exec app php artisan migrate --seed
```

Access the application at http://localhost

## Production Deployment

### 1. Prepare Environment

```bash
# Copy and configure environment
cp .env.docker .env

# Edit .env with your production values
nano .env
```

**Important environment variables:**
- `APP_KEY` - Generate with `php artisan key:generate --show`
- `APP_URL` - Your domain (https://yourdomain.com)
- `DB_PASSWORD` - Strong database password
- `REDIS_PASSWORD` - Redis password (optional but recommended)

### 2. Build and Start

```bash
# Build containers
docker compose -f docker-compose.prod.yml build

# Start services
docker compose -f docker-compose.prod.yml up -d

# Run migrations
docker compose -f docker-compose.prod.yml exec app php artisan migrate --force --seed
```

### 3. SSL Certificate (Let's Encrypt)

```bash
# Start certbot
docker compose -f docker-compose.prod.yml --profile ssl up -d certbot

# Get certificate (replace with your domain and email)
docker compose -f docker-compose.prod.yml exec certbot certbot certonly \
    --webroot \
    --webroot-path=/var/www/certbot \
    --email your@email.com \
    --agree-tos \
    --no-eff-email \
    -d yourdomain.com

# Update nginx config to enable HTTPS
# Edit docker/nginx/production.conf

# Restart nginx
docker compose -f docker-compose.prod.yml restart nginx
```

## Useful Commands

### Using Make (recommended)

```bash
make help          # Show all commands
make dev           # Start development
make up            # Start production
make logs          # View logs
make shell         # Access app container
make migrate       # Run migrations
make fresh         # Fresh migrate with seed
make backup        # Backup database
```

### Using Docker Compose directly

```bash
# View logs
docker compose logs -f

# Access app shell
docker compose exec app sh

# Run artisan command
docker compose exec app php artisan <command>

# Access database
docker compose exec db psql -U postgres -d zewalo

# Stop all services
docker compose down

# Stop and remove volumes (CAUTION: deletes data)
docker compose down -v
```

## Architecture

```
┌─────────────┐     ┌─────────────┐
│   Nginx     │────▶│   PHP-FPM   │
│   (Port 80) │     │   (App)     │
└─────────────┘     └──────┬──────┘
                           │
       ┌───────────────────┼───────────────────┐
       │                   │                   │
       ▼                   ▼                   ▼
┌─────────────┐     ┌─────────────┐     ┌─────────────┐
│ PostgreSQL  │     │    Redis    │     │   Queue     │
│   (DB)      │     │   (Cache)   │     │  (Worker)   │
└─────────────┘     └─────────────┘     └─────────────┘
```

## Volumes

- `zewalo-db-data` - PostgreSQL data
- `zewalo-redis-data` - Redis data
- `app-storage` - Laravel storage files
- `app-cache` - Laravel cache files

## Troubleshooting

### Container won't start
```bash
# Check logs
docker compose logs app

# Rebuild containers
docker compose build --no-cache
```

### Database connection refused
```bash
# Check if db is healthy
docker compose ps

# Check db logs
docker compose logs db
```

### Permission issues
```bash
# Fix storage permissions
docker compose exec app chown -R www-data:www-data storage bootstrap/cache
docker compose exec app chmod -R 775 storage bootstrap/cache
```

### Cache issues
```bash
# Clear all caches
docker compose exec app php artisan config:clear
docker compose exec app php artisan route:clear
docker compose exec app php artisan view:clear
docker compose exec app php artisan cache:clear
```

## Updating Application

```bash
# Pull latest changes
git pull origin main

# Rebuild containers
docker compose build

# Restart services
docker compose up -d

# Run migrations
docker compose exec app php artisan migrate --force

# Clear caches
docker compose exec app php artisan optimize:clear
docker compose exec app php artisan optimize
```

## Backup & Restore

### Backup
```bash
# Database
docker compose exec db pg_dump -U postgres zewalo > backup.sql

# Storage files
docker compose exec app tar -czf - storage > storage_backup.tar.gz
```

### Restore
```bash
# Database
docker compose exec -T db psql -U postgres zewalo < backup.sql

# Storage files
docker compose exec -T app tar -xzf - < storage_backup.tar.gz
```
