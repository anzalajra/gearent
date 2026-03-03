.PHONY: help build up down restart logs shell migrate seed fresh test

# Default target
help:
	@echo "Zewalo Docker Commands"
	@echo ""
	@echo "Development:"
	@echo "  make dev          - Start development environment"
	@echo "  make dev-down     - Stop development environment"
	@echo "  make dev-build    - Rebuild development containers"
	@echo ""
	@echo "Production:"
	@echo "  make build        - Build production containers"
	@echo "  make up           - Start production containers"
	@echo "  make down         - Stop containers"
	@echo "  make restart      - Restart containers"
	@echo ""
	@echo "Application:"
	@echo "  make migrate      - Run database migrations"
	@echo "  make seed         - Run database seeders"
	@echo "  make fresh        - Fresh migrate with seeding"
	@echo "  make optimize     - Optimize application for production"
	@echo "  make clear        - Clear all caches"
	@echo ""
	@echo "Utilities:"
	@echo "  make logs         - View container logs"
	@echo "  make shell        - Open shell in app container"
	@echo "  make db-shell     - Open PostgreSQL shell"
	@echo "  make test         - Run tests"

# Development
dev:
	docker compose -f docker-compose.dev.yml up -d
	@echo "Development environment started!"
	@echo "App: http://localhost"
	@echo "Vite: http://localhost:5173"

dev-down:
	docker compose -f docker-compose.dev.yml down

dev-build:
	docker compose -f docker-compose.dev.yml build --no-cache

dev-logs:
	docker compose -f docker-compose.dev.yml logs -f

# Production
build:
	docker compose build --no-cache

up:
	docker compose up -d
	@echo "Production environment started!"
	@echo "App: http://localhost"

down:
	docker compose down

restart:
	docker compose restart

logs:
	docker compose logs -f

# Application commands
shell:
	docker compose exec app sh

db-shell:
	docker compose exec db psql -U postgres -d zewalo

migrate:
	docker compose exec app php artisan migrate --force

seed:
	docker compose exec app php artisan db:seed --force

fresh:
	docker compose exec app php artisan migrate:fresh --seed --force

optimize:
	docker compose exec app php artisan config:cache
	docker compose exec app php artisan route:cache
	docker compose exec app php artisan view:cache
	docker compose exec app php artisan event:cache
	docker compose exec app php artisan icons:cache

clear:
	docker compose exec app php artisan config:clear
	docker compose exec app php artisan route:clear
	docker compose exec app php artisan view:clear
	docker compose exec app php artisan cache:clear

# Testing
test:
	docker compose exec app php artisan test

# Install dependencies
install:
	docker compose exec app composer install --no-dev --optimize-autoloader
	docker compose exec node npm ci --production

# Update dependencies
update:
	docker compose exec app composer update
	docker compose exec node npm update

# Backup database
backup:
	docker compose exec db pg_dump -U postgres zewalo > backup_$$(date +%Y%m%d_%H%M%S).sql
	@echo "Backup created!"

# Restore database
restore:
	@echo "Usage: make restore FILE=backup.sql"
	docker compose exec -T db psql -U postgres zewalo < $(FILE)
