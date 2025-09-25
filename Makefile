.PHONY: help install setup dev test clean migrate seed pint

# Default target
help:
	@echo "Available commands:"
	@echo "  install    - Install composer dependencies"
	@echo "  setup      - Setup development environment"
	@echo "  dev        - Start development environment"
	@echo "  test       - Run tests"
	@echo "  migrate    - Run database migrations"
	@echo "  seed       - Run database seeders"
	@echo "  pint       - Run Laravel Pint code formatting"
	@echo "  clean      - Clean cache and compiled files"

# Install dependencies
install:
	composer install --no-interaction --prefer-dist --optimize-autoloader

# Setup development environment
setup:
	@echo "Setting up development environment..."
	docker-compose up -d
	@echo "Waiting for database to be ready..."
	@timeout 30 sh -c 'until docker-compose exec mysql mysqladmin ping -h localhost --silent; do echo "Waiting for database..."; sleep 2; done'
	php artisan migrate:fresh --seed
	php artisan storage:link
	@echo "Development environment ready!"

# Start development environment
dev:
	docker-compose up -d
	php artisan serve

# Run tests
test:
	php artisan test

# Run database migrations
migrate:
	php artisan migrate

# Run database seeders
seed:
	php artisan db:seed

# Run Laravel Pint
pint:
	./vendor/bin/pint

# Clean cache and compiled files
clean:
	php artisan cache:clear
	php artisan config:clear
	php artisan route:clear
	php artisan view:clear
	composer dump-autoload
