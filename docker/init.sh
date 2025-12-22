#!/bin/bash

# MakanGuru Docker Initialization Script
# This script sets up the application for first-time Docker usage

set -e

echo "========================================="
echo "MakanGuru Docker Initialization"
echo "========================================="
echo ""

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# Check if .env exists
if [ ! -f .env ]; then
    echo -e "${YELLOW}No .env file found. Creating from .env.docker...${NC}"
    cp .env.docker .env
    echo -e "${GREEN}✓ .env file created${NC}"
else
    echo -e "${YELLOW}⚠ .env file already exists. Skipping...${NC}"
fi

# Generate application key if not set
echo ""
echo "Generating application key..."
docker compose exec app php artisan key:generate --ansi
echo -e "${GREEN}✓ Application key generated${NC}"

# Wait for MySQL to be ready
echo ""
echo "Waiting for MySQL to be ready..."
until docker compose exec mysql mysqladmin ping -h"localhost" --silent; do
    echo -e "${YELLOW}Waiting for MySQL connection...${NC}"
    sleep 2
done
echo -e "${GREEN}✓ MySQL is ready${NC}"

# Run migrations
echo ""
echo "Running database migrations..."
docker compose exec app php artisan migrate:fresh --seed --force
echo -e "${GREEN}✓ Database migrations completed${NC}"

# Clear and cache configuration
echo ""
echo "Optimizing application..."
docker compose exec app php artisan config:cache
docker compose exec app php artisan route:cache
docker compose exec app php artisan view:cache
echo -e "${GREEN}✓ Application optimized${NC}"

# Build frontend assets
echo ""
echo "Building frontend assets..."
docker compose run --rm node sh -c "npm install && npm run build"
echo -e "${GREEN}✓ Frontend assets built${NC}"

# Set proper permissions
echo ""
echo "Setting proper permissions..."
docker compose exec app chmod -R 775 storage bootstrap/cache
docker compose exec app chown -R www-data:www-data storage bootstrap/cache
echo -e "${GREEN}✓ Permissions set${NC}"

echo ""
echo "========================================="
echo -e "${GREEN}✓ Initialization Complete!${NC}"
echo "========================================="
echo ""
echo "Your MakanGuru application is ready!"
echo ""
echo -e "${GREEN}Access your application at: http://localhost:8080${NC}"
echo ""
echo "Useful commands:"
echo "  docker compose up -d          - Start all services"
echo "  docker compose down           - Stop all services"
echo "  docker compose logs -f app    - View application logs"
echo "  docker compose exec app bash  - Access app container shell"
echo ""
echo "Don't forget to configure your API keys in .env:"
echo "  - GEMINI_API_KEY (required)"
echo "  - GROQ_API_KEY (optional)"
echo ""
